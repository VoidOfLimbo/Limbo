<?php

namespace App\Enums;

enum EventVisibility: string
{
    case Private = 'private';
    case Shared = 'shared';
}
