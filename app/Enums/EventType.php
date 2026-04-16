<?php

namespace App\Enums;

enum EventType: string
{
    case Event = 'event';
    case Task = 'task';
    case MilestoneMarker = 'milestone_marker';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'Event',
            self::Task => 'Task',
            self::MilestoneMarker => 'Milestone Marker',
        };
    }
}
