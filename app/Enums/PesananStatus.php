<?php

namespace App\Enums;

enum PesananStatus: string
{
    case Nego  = 'nego';
    case Deal  = 'deal';
    case Batal = 'batal';
}
