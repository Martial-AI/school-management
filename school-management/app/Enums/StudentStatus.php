<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Transferred = 'transferred';
    case Left = 'left';
}
