<?php

namespace App\Http\Controllers\Planner;

use App\Enums\DeadlineType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Milestone;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlannerController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['status', 'priority', 'tags', 'date_from', 'date_to', 'show_snoozed']);

        $milestones = Milestone::query()
            ->forUser($user->id)
            ->withCount([
                'events as total_events_count',
                'events as completed_events_count' => fn ($q) => $q->where('status', 'completed'),
                'events as breach_count' => fn ($q) => $q->whereNotNull('end_at')
                    ->whereColumn('events.end_at', '>', 'milestones.end_at'),
            ])
            ->orderBy('created_at')
            ->get()
            ->map(function (Milestone $milestone) {
                $milestone->setAttribute('progress', $milestone->progress);
                $isBreached = $milestone->deadline_type === DeadlineType::Hard
                    && $milestone->end_at !== null
                    && (int) $milestone->breach_count > 0;
                $milestone->setAttribute('is_breached', $isBreached);
                // breach_count is only meaningful for hard milestones
                if ($milestone->deadline_type !== DeadlineType::Hard) {
                    $milestone->setAttribute('breach_count', 0);
                }

                return $milestone;
            });

        $milestoneParam = $request->string('milestone')->value();

        if ($milestoneParam === 'backlog') {
            $activeMilestoneId = null;
        } elseif ($milestoneParam) {
            $activeMilestoneId = $milestoneParam;
        } else {
            $activeMilestoneId = $milestones->first()?->id;
        }

        return Inertia::render('Planner/Index', [
            'milestones' => $milestones,
            'activeMilestoneId' => $activeMilestoneId,
            'filters' => $filters,
            'events' => Inertia::defer(fn () => $this->buildEventsQuery($user->id, $activeMilestoneId, $filters)->paginate(20)),
            'tags' => Inertia::defer(fn () => Tag::query()->where('user_id', $user->id)->orderBy('name')->get()),
        ]);
    }

    /** @return Builder<Event> */
    private function buildEventsQuery(string $userId, ?string $milestoneId, array $filters): Builder
    {
        /** @var Builder<Event> $query */
        $query = Event::query()
            ->forUser($userId)
            ->with([
                'tags',
                'milestone:id,title,deadline_type,end_at',
                'children' => fn ($q) => $q
                    ->with(['tags', 'milestone:id,title,deadline_type,end_at'])
                    ->orderBy('start_at'),
            ])
            ->whereNull('parent_event_id')
            ->orderByRaw('sort_order ASC NULLS LAST')
            ->orderBy('start_at');

        if ($milestoneId) {
            $query->forMilestone($milestoneId);
        } else {
            $query->backlog();
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->whereIn('priority', (array) $filters['priority']);
        }

        if (! empty($filters['tags'])) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', (array) $filters['tags']));
        }

        if (! empty($filters['date_from'])) {
            $query->where('start_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('start_at', '<=', $filters['date_to']);
        }

        if (empty($filters['show_snoozed']) || $filters['show_snoozed'] === 'false') {
            $query->active();
        }

        return $query;
    }
}
