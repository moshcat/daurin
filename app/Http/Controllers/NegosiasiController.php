<?php

namespace App\Http\Controllers;

use App\Enums\JenisSampah;
use App\Enums\NegosiaPengirim;
use App\Enums\PesananStatus;
use App\Models\Pesanan;
use App\Services\AcceptOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NegosiasiController extends Controller
{
    // ─── Industri views ──────────────────────────────────────────────────────

    /** Industri: view the negotiation thread for their order. */
    public function show(Pesanan $pesanan): Response
    {
        abort_unless($pesanan->industri_id === Auth::id(), 403);

        $pesanan->load(['negosiasi', 'bahanBaku.user', 'bahanBaku.sourceListing.user', 'industri']);

        $jenisSampahOptions = array_map(
            fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
            JenisSampah::cases()
        );

        return Inertia::render('Industri/Nego', compact('pesanan', 'jenisSampahOptions'));
    }

    /** Industri: add a counter-offer. */
    public function tawar(Request $request, Pesanan $pesanan): RedirectResponse
    {
        abort_unless($pesanan->industri_id === Auth::id(), 403);

        $data = $request->validate([
            'harga'   => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $pesanan->negosiasi()->create([
            'pengirim' => NegosiaPengirim::Industri->value,
            'harga'    => $data['harga'],
            'catatan'  => $data['catatan'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Penawaran diperbarui.');
    }

    /** Industri: accept the last pengepul price as the deal. */
    public function deal(Pesanan $pesanan, AcceptOffer $service): RedirectResponse
    {
        abort_unless($pesanan->industri_id === Auth::id(), 403);

        $lastHarga = (float) $pesanan->negosiasi()->latest()->value('harga');

        try {
            $service->handle($pesanan, $lastHarga);

            return redirect()->back()->with('success', 'Deal! Pesanan selesai.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /** Industri: cancel the order. */
    public function batal(Pesanan $pesanan): RedirectResponse
    {
        abort_unless($pesanan->industri_id === Auth::id(), 403);

        $pesanan->update(['status' => PesananStatus::Batal->value]);

        return redirect()->back()->with('success', 'Pesanan dibatalkan.');
    }

    // ─── Pengepul views ──────────────────────────────────────────────────────

    /** Pengepul: view the negotiation thread for an incoming order. */
    public function showForPengepul(Pesanan $pesanan): Response
    {
        abort_unless($pesanan->bahanBaku->user_id === Auth::id(), 403);

        $pesanan->load(['negosiasi', 'bahanBaku.user', 'bahanBaku.sourceListing.user', 'industri']);

        $jenisSampahOptions = array_map(
            fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
            JenisSampah::cases()
        );

        return Inertia::render('Pengepul/Nego', compact('pesanan', 'jenisSampahOptions'));
    }

    /** Pengepul: add a counter-offer. */
    public function balas(Request $request, Pesanan $pesanan): RedirectResponse
    {
        abort_unless($pesanan->bahanBaku->user_id === Auth::id(), 403);

        $data = $request->validate([
            'harga'   => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $pesanan->negosiasi()->create([
            'pengirim' => NegosiaPengirim::Pengepul->value,
            'harga'    => $data['harga'],
            'catatan'  => $data['catatan'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Balasan dikirim.');
    }

    /** Pengepul: accept the deal at the last offered price. */
    public function dealByPengepul(Pesanan $pesanan, AcceptOffer $service): RedirectResponse
    {
        abort_unless($pesanan->bahanBaku->user_id === Auth::id(), 403);

        $lastHarga = (float) $pesanan->negosiasi()->latest()->value('harga');

        try {
            $service->handle($pesanan, $lastHarga);

            return redirect()->back()->with('success', 'Deal! Pesanan selesai.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }
}
