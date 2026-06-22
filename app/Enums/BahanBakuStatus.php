<?php

namespace App\Enums;

enum BahanBakuStatus: string
{
    case Tersedia = 'tersedia';
    case Dilelang = 'dilelang';
    case Terjual  = 'terjual';
}
