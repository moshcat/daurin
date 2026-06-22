<?php

namespace App\Enums;

enum LelangStatus: string
{
    case Berlangsung = 'berlangsung'; // lelang terbuka, menerima bid
    case Selesai     = 'selesai';     // ditutup, ada pemenang
    case Gagal       = 'gagal';       // ditutup tanpa bid / reserve tak tercapai
    case Batal       = 'batal';       // dibatalkan pengepul (hanya bila belum ada bid)

    public function label(): string
    {
        return match ($this) {
            self::Berlangsung => 'Berlangsung',
            self::Selesai     => 'Selesai',
            self::Gagal       => 'Gagal',
            self::Batal       => 'Dibatalkan',
        };
    }
}
