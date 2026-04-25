<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class PlannerExportController extends Controller
{
    public function fullPlanner(Request $request): Response
    {
        $events = Event::query()
            ->forUser($request->user()->id)
            ->whereNotNull('start_at')
            ->get();

        return $this->icsResponse($events, 'limbo-planner.ics');
    }

    public function singleEvent(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        return $this->icsResponse(collect([$event]), "limbo-event-{$event->id}.ics");
    }

    public function milestoneExport(Request $request, Milestone $milestone): Response
    {
        $this->authorize('view', $milestone);

        $events = $milestone->events()->whereNotNull('start_at')->get();

        return $this->icsResponse($events, "limbo-milestone-{$milestone->id}.ics");
    }

    private function icsResponse(Collection $events, string $filename): Response
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Limbo//Life Planner//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$event->id.'@limbo';
            $lines[] = 'SUMMARY:'.$this->escapeIcs($event->title);
            $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.($event->start_at?->format('Ymd\THis\Z') ?? now()->format('Ymd\THis\Z'));

            if ($event->end_at) {
                $lines[] = 'DTEND:'.$event->end_at->format('Ymd\THis\Z');
            }

            if ($event->description) {
                $lines[] = 'DESCRIPTION:'.$this->escapeIcs($event->description);
            }

            if ($event->location) {
                $lines[] = 'LOCATION:'.$this->escapeIcs($event->location);
            }

            $lines[] = 'STATUS:'.$event->status->icsStatus();
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function escapeIcs(string $value): string
    {
        return str_replace(["\r\n", "\n", "\r", ',', ';', '\\'], ['\\n', '\\n', '\\n', '\\,', '\\;', '\\\\'], $value);
    }
}
