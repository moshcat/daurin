<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Tersedia = 'tersedia';
    case Diambil  = 'diambil';
    case Terjual  = 'terjual';
}
