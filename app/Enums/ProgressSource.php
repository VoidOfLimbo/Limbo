<?php

namespace App\Enums;

enum ProgressSource: string
{
    case Derived = 'derived';
    case Manual = 'manual';
}
