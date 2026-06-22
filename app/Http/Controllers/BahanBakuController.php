<?php

namespace App\Http\Controllers;

use App\Enums\BahanBakuStatus;
use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Models\BahanBaku;
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
            ->with(['sourceListing', 'pesanan'])
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
            'berat'        => ['required', 'numeric', 'min:0.01'],
            'harga_awal'   => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $service->handle(Auth::user(), $listing, $data);

            return redirect()->route('pengepul.bahanbaku.index')
                ->with('success', 'Bahan baku berhasil dibuat.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /** Industri: browse all available bahan baku. */
    public function indexForIndustri(): Response
    {
        $bahanBaku = BahanBaku::with(['user', 'sourceListing'])
            ->where('status', BahanBakuStatus::Tersedia->value)
            ->latest()
            ->get();

        $jenisSampahOptions = array_map(
            fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
            JenisSampah::cases()
        );

        return Inertia::render('Industri/Index', compact('bahanBaku', 'jenisSampahOptions'));
    }
}
