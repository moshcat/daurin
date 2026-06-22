<?php

namespace App\Enums;

enum UserRole: string
{
    case RumahTangga = 'rumah_tangga';
    case Pengepul    = 'pengepul';
    case Industri    = 'industri';

    public function label(): string
    {
        return match ($this) {
            self::RumahTangga => 'Rumah Tangga',
            self::Pengepul    => 'Pengepul',
            self::Industri    => 'Industri Pengolah',
        };
    }
}
