<?php

namespace App\Compatibility\Eds;

enum CompatibilityProfile: string
{
    case EdsC0 = 'EDS-C0';
    case EdsC1 = 'EDS-C1';
    case EdsC2 = 'EDS-C2';
    case EdsC3 = 'EDS-C3';
    case EdsC4 = 'EDS-C4';
    case EdsC5 = 'EDS-C5';
}
