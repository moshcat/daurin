<?php

namespace App\Http\Controllers;

use App\Enums\JenisSampah;
use App\Models\PengepulJenis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PengepulJenisController extends Controller
{
    public function index(): Response
    {
        $jenisList = Auth::user()->pengepulJenis()->get();

        $allJenis = array_map(
            fn (JenisSampah $j) => ['value' => $j->value, 'label' => $j->label()],
            JenisSampah::cases()
        );

        return Inertia::render('Pengepul/Jenis', [
            'jenisList' => $jenisList,
            'allJenis'  => $allJenis,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis_sampah' => ['required', 'string'],
        ]);

        Auth::user()->pengepulJenis()->firstOrCreate([
            'jenis_sampah' => $data['jenis_sampah'],
        ]);

        return redirect()->back()->with('success', 'Jenis sampah ditambahkan.');
    }

    public function destroy(PengepulJenis $jenis): RedirectResponse
    {
        abort_if($jenis->user_id !== Auth::id(), 403);

        $jenis->delete();

        return redirect()->back()->with('success', 'Jenis sampah dihapus.');
    }
}
