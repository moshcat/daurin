<?php

namespace App\Support;

use App\Enums\JenisSampah;

/**
 * Estimasi faktor emisi CO₂ yang dihemat per kg material yang didaur ulang
 * (kg CO₂e / kg). Angka adalah ASUMSI demo berbasis rentang publik untuk
 * recycled vs virgin material — bukan nilai resmi, cukup untuk visualisasi dampak.
 */
class ImpactFactors
{
    /** @var array<string, float> */
    private const FACTORS = [
        'plastik_pet'  => 1.5,
        'plastik_hdpe' => 1.4,
        'kertas'       => 0.9,
        'kardus'       => 0.9,
        'logam'        => 2.0,
        'kaleng'       => 1.8,
        'kaca'         => 0.3,
        'elektronik'   => 1.2,
    ];

    /** CO₂e saved (kg) for a given waste type and weight. */
    public static function co2Saved(JenisSampah|string $jenis, float $beratKg): float
    {
        $key = $jenis instanceof JenisSampah ? $jenis->value : $jenis;

        return round(($beratKg * (self::FACTORS[$key] ?? 1.0)), 2);
    }
}
