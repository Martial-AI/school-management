<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case Manual = 'manual';
    case QrCode = 'qr_code';
    case Biometric = 'biometric';
}
