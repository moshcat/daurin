<?php

namespace App\Http\Controllers;

use App\Enums\BahanBakuStatus;
use App\Enums\BahanJadiStatus;
use App\Enums\ListingStatus;
use App\Models\BahanBaku;
use App\Models\BahanJadi;
use App\Models\ListingSampah;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function index(): Response
    {
        // Layer 1 — sampah RT (paginated + merge for infinite scroll).
        $listings = ListingSampah::with('user')
            ->where('status', ListingStatus::Tersedia->value)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // Layer 2 — bahan baku pengepul (with lineage + active auction for the buy CTA).
        $bahanBaku = BahanBaku::with(['user', 'sourceListing.user', 'lelang'])
            ->whereIn('status', [BahanBakuStatus::Tersedia->value, BahanBakuStatus::Dilelang->value])
            ->latest()
            ->limit(24)
            ->get();

        // Layer 3 — bahan baku jadi industri (full lineage RT → Pengepul → Industri).
        $bahanJadi = BahanJadi::with(['user', 'sourcePesanan.bahanBaku.sourceListing.user'])
            ->where('status', BahanJadiStatus::Tersedia->value)
            ->latest()
            ->limit(24)
            ->get();

        return Inertia::render('Welcome', [
            // Appended on partial reloads (Inertia merge) — powers infinite scroll.
            'listings' => Inertia::merge(fn () => $listings->items()),
            'pagination' => [
                'page' => $listings->currentPage(),
                'hasMore' => $listings->hasMorePages(),
            ],
            'bahanBaku' => $bahanBaku,
            'bahanJadi' => $bahanJadi,
            'stats' => [
                'listingCount' => ListingSampah::where('status', ListingStatus::Tersedia->value)->count(),
                'bahanBakuCount' => BahanBaku::where('status', BahanBakuStatus::Tersedia->value)->count(),
                'bahanJadiCount' => BahanJadi::where('status', BahanJadiStatus::Tersedia->value)->count(),
            ],
        ]);
    }
}
