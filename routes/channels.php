<?php

use App\Models\Lelang;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Presence channel ruang lelang. Semua user terautentikasi boleh menonton;
 * identitas ringkas dikembalikan agar UI bisa menampilkan jumlah penonton.
 */
Broadcast::channel('lelang.{lelang}', function (User $user, Lelang $lelang) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->role->value,
    ];
});
