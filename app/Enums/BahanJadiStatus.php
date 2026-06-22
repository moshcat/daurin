<?php

namespace App\Enums;

enum BahanJadiStatus: string
{
    case Tersedia = 'tersedia';
    case Terjual  = 'terjual';

    public function label(): string
    {
        return match ($this) {
            self::Tersedia => 'Tersedia',
            self::Terjual  => 'Terjual',
        };
    }
}
