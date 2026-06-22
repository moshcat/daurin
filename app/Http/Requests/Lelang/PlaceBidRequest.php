<?php

namespace App\Http\Requests\Lelang;

use Illuminate\Foundation\Http\FormRequest;

class PlaceBidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Aturan penawar diperiksa di service (PlaceBid).
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'harga' => ['required', 'numeric', 'min:0'],
        ];
    }
}
