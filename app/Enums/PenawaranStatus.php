<?php

namespace App\Enums;

enum PenawaranStatus: string
{
    case Diajukan = 'diajukan';
    case Diterima = 'diterima';
    case Ditolak  = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Menunggu',
            self::Diterima => 'Diterima',
            self::Ditolak  => 'Ditolak',
        };
    }
}
