<?php

namespace App\Http\Controllers;

use App\Enums\NegosiaPengirim;
use App\Enums\PesananStatus;
use App\Models\Pesanan;
use App\Notifications\PembayaranDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PesananController extends Controller
{
    /** Industri: open a new order with an initial offer. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bahan_baku_id' => ['required', 'integer', 'exists:bahan_baku,id'],
            'harga'         => ['required', 'numeric', 'min:0'],
            'catatan'       => ['nullable', 'string', 'max:1000'],
        ]);

        $pesanan = DB::transaction(function () use ($data): Pesanan {
            $pesanan = Pesanan::create([
                'bahan_baku_id' => $data['bahan_baku_id'],
                'industri_id'   => Auth::id(),
                'status'        => PesananStatus::Nego->value,
            ]);

            $pesanan->negosiasi()->create([
                'pengirim' => NegosiaPengirim::Industri->value,
                'harga'    => $data['harga'],
                'catatan'  => $data['catatan'] ?? null,
            ]);

            return $pesanan;
        });

        return redirect()->route('industri.nego.show', $pesanan)
            ->with('success', 'Penawaran dikirim.');
    }

    /** Industri: bayar pesanan yang sudah deal (simulasi pembayaran). */
    public function bayar(Pesanan $pesanan): RedirectResponse
    {
        abort_unless($pesanan->industri_id === Auth::id(), 403);

        if ($pesanan->status !== PesananStatus::Deal) {
            return back()->withErrors(['pesanan' => 'Pesanan belum berstatus deal.']);
        }

        if ($pesanan->sudahDibayar()) {
            return back()->with('success', 'Pesanan sudah lunas.');
        }

        $pesanan->update(['dibayar_at' => now()]);

        // Notifikasi ke penjual (best-effort).
        $pesanan->loadMissing('bahanBaku.user');
        rescue(fn () => $pesanan->bahanBaku->user?->notify(new PembayaranDiterima($pesanan)));

        return back()->with('success', 'Pembayaran berhasil (simulasi). Pesanan LUNAS.');
    }
}
