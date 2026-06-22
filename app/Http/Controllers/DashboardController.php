<?php

namespace App\Http\Controllers;

use App\Enums\BahanBakuStatus;
use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Enums\PesananStatus;
use App\Enums\UserRole;
use App\Models\BahanBaku;
use App\Models\ListingSampah;
use App\Models\Pesanan;
use App\Support\ImpactFactors;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $stats = [
            'total_listing' => ListingSampah::count(),
            'total_bahan_baku' => BahanBaku::count(),
            'total_deal' => Pesanan::where('status', PesananStatus::Deal->value)->count(),
            'total_nilai_deal' => Pesanan::where('status', PesananStatus::Deal->value)->sum('harga_sepakat'),
        ];

        // ── Impact metrics (sustainability) ──────────────────────────────────
        // kg recycled = material that completed the chain (listing → terjual).
        $terjualPerJenis = ListingSampah::where('status', ListingStatus::Terjual->value)
            ->selectRaw('jenis_sampah, SUM(berat) as kg')
            ->groupBy('jenis_sampah')
            ->get();

        $kgRecycled = 0.0;
        $co2Saved = 0.0;
        foreach ($terjualPerJenis as $row) {
            $kgRecycled += (float) $row->kg;
            $co2Saved += ImpactFactors::co2Saved($row->jenis_sampah, (float) $row->kg);
        }

        // Marketplace composition (all listings) — feeds the bar chart.
        $volByJenis = ListingSampah::selectRaw('jenis_sampah, SUM(berat) as kg')
            ->groupBy('jenis_sampah')
            ->get()
            ->keyBy(fn ($r) => $r->jenis_sampah->value);

        $stats['kg_recycled'] = round($kgRecycled, 2);
        $stats['co2_saved_kg'] = round($co2Saved, 2);
        $stats['nilai_ekonomi'] = $stats['total_nilai_deal'];
        $stats['volume_per_jenis'] = array_map(
            fn (JenisSampah $j) => [
                'jenis' => $j->value,
                'label' => $j->label(),
                'kg' => round((float) ($volByJenis[$j->value]->kg ?? 0), 2),
            ],
            JenisSampah::cases()
        );

        if ($user->role === UserRole::RumahTangga) {
            $stats['my_listing'] = $user->listingSampah()->count();
            $stats['my_listing_tersedia'] = $user->listingSampah()
                ->where('status', ListingStatus::Tersedia->value)->count();
            $stats['my_listing_diambil'] = $user->listingSampah()
                ->where('status', ListingStatus::Diambil->value)->count();
            $stats['my_listing_terjual'] = $user->listingSampah()
                ->where('status', ListingStatus::Terjual->value)->count();
        } elseif ($user->role === UserRole::Pengepul) {
            $stats['my_claimed'] = ListingSampah::where('claimed_by', $user->id)->count();
            $stats['my_bahan_baku'] = $user->bahanBaku()->count();
            $stats['my_bahan_baku_tersedia'] = $user->bahanBaku()
                ->where('status', BahanBakuStatus::Tersedia->value)->count();
            $stats['my_pesanan_nego'] = Pesanan::whereHas('bahanBaku', fn ($q) => $q->where('user_id', $user->id))
                ->where('status', PesananStatus::Nego->value)->count();
            $stats['my_pesanan_deal'] = Pesanan::whereHas('bahanBaku', fn ($q) => $q->where('user_id', $user->id))
                ->where('status', PesananStatus::Deal->value)->count();
        } elseif ($user->role === UserRole::Industri) {
            $stats['my_pesanan_nego'] = $user->pesanan()
                ->where('status', PesananStatus::Nego->value)->count();
            $stats['my_pesanan_deal'] = $user->pesanan()
                ->where('status', PesananStatus::Deal->value)->count();
            $stats['my_pesanan_batal'] = $user->pesanan()
                ->where('status', PesananStatus::Batal->value)->count();
            $stats['my_nilai_deal'] = $user->pesanan()
                ->where('status', PesananStatus::Deal->value)->sum('harga_sepakat');
        }

        return Inertia::render('Dashboard', compact('stats'));
    }
}
