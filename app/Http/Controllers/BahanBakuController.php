<?php

namespace App\Http\Controllers;

use App\Enums\BahanBakuStatus;
use App\Enums\JenisSampah;
use App\Enums\LelangStatus;
use App\Enums\ListingStatus;
use App\Models\BahanBaku;
use App\Models\Lelang;
use App\Models\ListingSampah;
use App\Services\CreateBahanBaku;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BahanBakuController extends Controller
{
    /** Pengepul: list their own bahan baku. */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $bahanBaku = $user->bahanBaku()
            ->with(['sourceListing', 'pesanan', 'lelang'])
            ->latest()
            ->get();

        // Listings claimed by this pengepul that haven't been converted yet
        $claimedListings = ListingSampah::with('user')
            ->where('claimed_by', $user->id)
            ->where('status', ListingStatus::Diambil->value)
            ->get();

        $jenisSampahOptions = array_map(
            fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
            JenisSampah::cases()
        );

        return Inertia::render('Pengepul/BahanBaku', compact('bahanBaku', 'claimedListings', 'jenisSampahOptions'));
    }

    /** Pengepul: convert a claimed listing into a bahan baku entry. */
    public function store(Request $request, ListingSampah $listing, CreateBahanBaku $service): RedirectResponse
    {
        $data = $request->validate([
            'jenis_sampah' => ['required', 'string'],
            'peruntukan'   => ['nullable', 'string', 'max:255'],
            'berat'        => ['required', 'numeric', 'gt:0', 'max:'.$listing->berat],
            'harga_awal'   => ['required', 'numeric', 'min:0'],
        ], [
            'berat.gt'  => 'Berat bahan baku harus lebih dari 0 kg.',
            'berat.max' => 'Berat bahan baku tidak boleh melebihi berat listing asal ('.$listing->berat.' kg).',
        ]);

        try {
            $service->handle(Auth::user(), $listing, $data);

            return redirect()->route('pengepul.bahanbaku.index')
                ->with('success', 'Bahan baku berhasil dibuat.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /** Industri: browse live auctions to bid on. */
    public function indexForIndustri(): Response
    {
        $lelangs = Lelang::with(['bahanBaku.user', 'highestBid'])
            ->where('status', LelangStatus::Berlangsung->value)
            ->latest()
            ->get()
            ->map(fn (Lelang $lelang): array => [
                'id' => $lelang->id,
                'jenis_sampah' => $lelang->bahanBaku->jenis_sampah->value,
                'jenis_label' => $lelang->bahanBaku->jenis_sampah->label(),
                'berat' => (float) $lelang->bahanBaku->berat,
                'peruntukan' => $lelang->bahanBaku->peruntukan,
                'pengepul' => $lelang->bahanBaku->user->name,
                'harga_awal' => (float) $lelang->harga_awal,
                'harga_tertinggi' => $lelang->hargaTertinggi() !== null ? (float) $lelang->hargaTertinggi() : null,
                'jumlah_bid' => $lelang->bids()->count(),
                'waktu_selesai' => $lelang->waktu_selesai->toIso8601String(),
            ]);

        $jenisSampahOptions = array_map(
            fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
            JenisSampah::cases()
        );

        return Inertia::render('Industri/Index', compact('lelangs', 'jenisSampahOptions'));
    }
}
