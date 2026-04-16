<?php

namespace App\Enums;

enum DependencyType: string
{
    case Blocking = 'blocking';
    case Informational = 'informational';
}
