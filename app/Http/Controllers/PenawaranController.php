<?php

namespace App\Http\Controllers;

use App\Models\ListingSampah;
use App\Models\PenawaranListing;
use App\Services\Penawaran\AjukanPenawaran;
use App\Services\Penawaran\TerimaPenawaran;
use App\Services\Penawaran\TolakPenawaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PenawaranController extends Controller
{
    /** Pengepul: ajukan penawaran harga untuk sebuah listing. */
    public function ajukan(Request $request, ListingSampah $listing, AjukanPenawaran $service): RedirectResponse
    {
        $data = $request->validate([
            'harga' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $service->handle(Auth::user(), $listing, (float) $data['harga']);

            return back()->with('success', 'Penawaran terkirim ke rumah tangga.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /** RT: terima penawaran (listing menjadi diambil pengepul). */
    public function terima(PenawaranListing $penawaran, TerimaPenawaran $service): RedirectResponse
    {
        try {
            $service->handle(Auth::user(), $penawaran);

            return back()->with('success', 'Penawaran diterima.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /** RT: tolak penawaran. */
    public function tolak(PenawaranListing $penawaran, TolakPenawaran $service): RedirectResponse
    {
        try {
            $service->handle(Auth::user(), $penawaran);

            return back()->with('success', 'Penawaran ditolak.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
