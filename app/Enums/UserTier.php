<?php

namespace App\Enums;

enum UserTier: string
{
    case Free = 'free';
    case Premium = 'premium';
    case Loyalist = 'loyalist';
}
