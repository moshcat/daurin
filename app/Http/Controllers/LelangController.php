<?php

namespace App\Http\Controllers;

use App\Enums\JenisSampah;
use App\Http\Requests\Lelang\PlaceBidRequest;
use App\Http\Requests\Lelang\StartLelangRequest;
use App\Models\BahanBaku;
use App\Models\Lelang;
use App\Models\LelangBid;
use App\Services\Lelang\CancelLelang;
use App\Services\Lelang\PlaceBid;
use App\Services\Lelang\StartLelang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LelangController extends Controller
{
    /** Ruang lelang real-time (industri menonton & menawar, pengepul memantau). */
    public function show(Lelang $lelang): Response
    {
        $lelang->load([
            'bahanBaku.user',
            'bahanBaku.sourceListing.user',
            'bids.industri',
            'highestBid',
            'pemenang',
            'pesanan',
        ]);

        return Inertia::render('Lelang/Room', [
            'lelang' => $this->present($lelang),
            'canBid' => Auth::user()->isIndustri() && $lelang->bahanBaku->user_id !== Auth::id(),
            'isOwner' => $lelang->bahanBaku->user_id === Auth::id(),
            'isWinner' => $lelang->pemenang_id !== null && $lelang->pemenang_id === Auth::id(),
            'pesanan' => $lelang->pesanan ? [
                'id' => $lelang->pesanan->id,
                'sudah_dibayar' => $lelang->pesanan->sudahDibayar(),
            ] : null,
        ]);
    }

    /** Pengepul: buka lelang atas bahan bakunya. */
    public function start(StartLelangRequest $request, BahanBaku $bahanBaku, StartLelang $service): RedirectResponse
    {
        abort_unless($bahanBaku->user_id === Auth::id(), 403);

        try {
            $lelang = $service->handle($bahanBaku, $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('lelang.show', $lelang)->with('success', 'Negosiasi harga dibuka.');
    }

    /** Industri: menaikkan tawaran. */
    public function bid(PlaceBidRequest $request, Lelang $lelang, PlaceBid $service): RedirectResponse
    {
        try {
            $service->handle($lelang, Auth::user(), (float) $request->validated()['harga']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Tawaran terkirim.');
    }

    /** Industri: beli-langsung (buyout). */
    public function buyout(Lelang $lelang, PlaceBid $service): RedirectResponse
    {
        try {
            $service->handle($lelang, Auth::user(), 0.0, isBuyout: true);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Beli-langsung berhasil. Anda memenangkan negosiasi.');
    }

    /** Pengepul: batalkan lelang (hanya bila belum ada tawaran). */
    public function cancel(Lelang $lelang, CancelLelang $service): RedirectResponse
    {
        abort_unless($lelang->bahanBaku->user_id === Auth::id(), 403);

        try {
            $service->handle($lelang);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Negosiasi dibatalkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Lelang $lelang): array
    {
        return [
            'id' => $lelang->id,
            'status' => $lelang->status->value,
            'harga_awal' => (float) $lelang->harga_awal,
            'kelipatan' => (float) $lelang->kelipatan,
            'harga_buyout' => $lelang->harga_buyout !== null ? (float) $lelang->harga_buyout : null,
            'harga_tertinggi' => $lelang->hargaTertinggi() !== null ? (float) $lelang->hargaTertinggi() : null,
            'minimal_bid' => (float) $lelang->minimalBidBerikutnya(),
            'waktu_selesai' => $lelang->waktu_selesai->toIso8601String(),
            'pemenang_nama' => $lelang->pemenang ? Str::mask($lelang->pemenang->name, '*', 4) : null,
            'harga_final' => $lelang->harga_final !== null ? (float) $lelang->harga_final : null,
            'bahan_baku' => [
                'jenis_sampah' => $lelang->bahanBaku->jenis_sampah->value,
                'jenis_label' => $lelang->bahanBaku->jenis_sampah->label(),
                'berat' => (float) $lelang->bahanBaku->berat,
                'peruntukan' => $lelang->bahanBaku->peruntukan,
                'pengepul' => $lelang->bahanBaku->user->name,
                'source' => $lelang->bahanBaku->sourceListing ? [
                    'jenis_label' => $lelang->bahanBaku->sourceListing->jenis_sampah->label(),
                    'berat' => (float) $lelang->bahanBaku->sourceListing->berat,
                    'rt' => $lelang->bahanBaku->sourceListing->user?->name,
                ] : null,
            ],
            'bids' => $lelang->bids
                ->sortByDesc('id')
                ->values()
                ->map(fn (LelangBid $b): array => [
                    'id' => $b->id,
                    'industri_nama' => Str::mask($b->industri->name, '*', 4),
                    'harga' => (float) $b->harga,
                    'is_buyout' => $b->is_buyout,
                    'created_at' => $b->created_at->toIso8601String(),
                ]),
        ];
    }
}
