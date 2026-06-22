<?php

namespace App\Enums;

enum JenisSampah: string
{
    case PlastikPet  = 'plastik_pet';
    case PlastikHdpe = 'plastik_hdpe';
    case Kertas      = 'kertas';
    case Kardus      = 'kardus';
    case Logam       = 'logam';
    case Kaleng      = 'kaleng';
    case Kaca        = 'kaca';
    case Elektronik  = 'elektronik';

    public function label(): string
    {
        return match ($this) {
            self::PlastikPet  => 'Plastik PET',
            self::PlastikHdpe => 'Plastik HDPE',
            self::Kertas      => 'Kertas',
            self::Kardus      => 'Kardus',
            self::Logam       => 'Logam',
            self::Kaleng      => 'Kaleng',
            self::Kaca        => 'Kaca',
            self::Elektronik  => 'Elektronik',
        };
    }
}
