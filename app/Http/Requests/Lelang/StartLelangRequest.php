<?php

namespace App\Http\Requests\Lelang;

use Illuminate\Foundation\Http\FormRequest;

class StartLelangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kepemilikan diperiksa di controller (abort_unless).
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'harga_awal' => ['required', 'numeric', 'min:0'],
            'kelipatan' => ['nullable', 'numeric', 'min:1'],
            'durasi_menit' => ['nullable', 'integer', 'min:1', 'max:43200'],
            'harga_reserve' => ['nullable', 'numeric', 'min:0'],
            'harga_buyout' => ['nullable', 'numeric', 'gt:harga_awal'],
        ];
    }
}
