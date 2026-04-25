<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Upcoming = 'upcoming';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Skipped = 'skipped';

    public function icsStatus(): string
    {
        return match ($this) {
            self::Draft, self::Upcoming => 'TENTATIVE',
            self::InProgress => 'CONFIRMED',
            self::Completed => 'COMPLETED',
            self::Cancelled, self::Skipped => 'CANCELLED',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Upcoming => 'Upcoming',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Skipped => 'Skipped',
        };
    }
}
