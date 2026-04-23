<?php

namespace App\Enums;

enum MilestonePriority: string
{
    case Ignorable = 'ignorable';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Ignorable => 'Ignorable',
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }
}
