<?php

namespace App\Http\Controllers;

use App\Enums\BahanJadiStatus;
use App\Enums\JenisSampah;
use App\Enums\PesananStatus;
use App\Models\BahanJadi;
use App\Models\Pesanan;
use App\Services\CreateBahanJadi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BahanJadiController extends Controller
{
    /** Industri: deals ready to process + own finished materials. */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Completed deals that have not yet been processed into bahan jadi.
        $processedPesananIds = BahanJadi::where('user_id', $user->id)
            ->whereNotNull('source_pesanan_id')
            ->pluck('source_pesanan_id');

        $deals = $user->pesanan()
            ->where('status', PesananStatus::Deal->value)
            ->whereNotIn('id', $processedPesananIds)
            ->with('bahanBaku.sourceListing.user')
            ->latest()
            ->get();

        $bahanJadi = BahanJadi::where('user_id', $user->id)
            ->with('sourcePesanan.bahanBaku.sourceListing.user')
            ->latest()
            ->get();

        $jenisSampahOptions = array_map(
            fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
            JenisSampah::cases()
        );

        return Inertia::render('Industri/BahanJadi', compact('deals', 'bahanJadi', 'jenisSampahOptions'));
    }

    /** Industri: process a deal into a sellable finished material. */
    public function store(Request $request, CreateBahanJadi $service): RedirectResponse
    {
        $data = $request->validate([
            'source_pesanan_id' => ['required', 'integer', 'exists:pesanan,id'],
            'nama'              => ['required', 'string', 'max:255'],
            'jenis_sampah'      => ['required', 'string'],
            'deskripsi'         => ['nullable', 'string', 'max:1000'],
            'berat'             => ['required', 'numeric', 'min:0.01'],
            'harga'             => ['required', 'numeric', 'min:0'],
            'foto'              => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $pesanan = Pesanan::findOrFail($data['source_pesanan_id']);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('foto-bahan-jadi', 'public');
        }

        try {
            $service->handle(Auth::user(), $pesanan, [
                'nama'         => $data['nama'],
                'jenis_sampah' => $data['jenis_sampah'],
                'deskripsi'    => $data['deskripsi'] ?? null,
                'berat'        => $data['berat'],
                'harga'        => $data['harga'],
                'foto_path'    => $fotoPath,
                'status'       => BahanJadiStatus::Tersedia->value,
            ]);

            return redirect()->route('industri.bahanjadi.index')
                ->with('success', 'Bahan baku jadi berhasil dibuat.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }
}
