<?php

namespace App\Http\Controllers;

use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Models\ListingSampah;
use App\Services\ClaimListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ListingSampahController extends Controller
{
    // ─── Rumah Tangga ────────────────────────────────────────────────────────

    public function index(): Response
    {
        $listings = Auth::user()->listingSampah()->latest()->get();

        return Inertia::render('Rt/Index', compact('listings'));
    }

    public function create(): Response
    {
        return Inertia::render('Rt/Create', [
            'jenisSampahOptions' => array_map(
                fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
                JenisSampah::cases()
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis_sampah'   => ['required', 'string'],
            'berat'          => ['required', 'numeric', 'min:0.01'],
            'harga'          => ['required', 'numeric', 'min:0'],
            'foto'           => ['nullable', 'file', 'image', 'max:5120'],
            'ai_label'       => ['nullable', 'string'],
            'ai_confidence'  => ['nullable', 'numeric', 'min:0', 'max:1'],
            'lat'            => ['nullable', 'numeric'],
            'lng'            => ['nullable', 'numeric'],
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('foto-sampah', 'public');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        ListingSampah::create([
            'user_id'        => $user->id,
            'jenis_sampah'   => $data['jenis_sampah'],
            'berat'          => $data['berat'],
            'harga'          => $data['harga'],
            'foto_path'      => $fotoPath,
            'ai_label'       => $data['ai_label'] ?? null,
            'ai_confidence'  => $data['ai_confidence'] ?? null,
            'lat'            => $data['lat'] ?? $user->lat,
            'lng'            => $data['lng'] ?? $user->lng,
            'status'         => ListingStatus::Tersedia->value,
        ]);

        return redirect()->route('rt.index')->with('success', 'Listing berhasil dibuat.');
    }

    public function destroy(ListingSampah $listing): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        abort_if(
            $listing->user_id !== $user->id || $listing->status !== ListingStatus::Tersedia,
            403,
            'Tidak dapat menghapus listing ini.'
        );

        $listing->delete();

        return redirect()->back()->with('success', 'Listing dihapus.');
    }

    // ─── Pengepul ────────────────────────────────────────────────────────────

    public function ketersediaan(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $handledJenis = $user->pengepulJenis()->pluck('jenis_sampah')->toArray();

        $listings = ListingSampah::with('user')
            ->where('status', ListingStatus::Tersedia->value)
            ->whereIn('jenis_sampah', $handledJenis)
            ->latest()
            ->get();

        return Inertia::render('Pengepul/Ketersediaan', [
            'listings'     => $listings,
            'handledJenis' => $handledJenis,
        ]);
    }

    public function klaim(ListingSampah $listing, ClaimListing $service): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $service->handle($user, $listing);

            return redirect()->back()->with('success', 'Listing berhasil diklaim.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }
}
