<?php

namespace App\Http\Controllers\Planner;

use App\Enums\DeadlineType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planner\SnoozeEventRequest;
use App\Http\Requests\Planner\StoreEventRequest;
use App\Http\Requests\Planner\UpdateEventRequest;
use App\Models\Event;
use App\Models\Milestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->forUser($request->user()->id)
            ->with(['tags', 'milestone:id,title,deadline_type,end_at'])
            ->active()
            ->orderBy('start_at')
            ->paginate(20);

        return response()->json($events);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $request->user()->events()->create($request->validated());

        $this->recalculateMilestoneIfNeeded($event);

        return back();
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return response()->json($event->load(['tags', 'milestone', 'children', 'reminders']));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->update($request->validated());

        $this->recalculateMilestoneIfNeeded($event->fresh());

        return back();
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $milestoneId = $event->milestone_id;
        $event->delete();

        if ($milestoneId) {
            $milestone = Milestone::find($milestoneId);
            $milestone?->recalculateDerivedDates();
        }

        return back();
    }

    public function snooze(SnoozeEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('snooze', $event);

        $event->update([
            'snoozed_until' => $request->validated('snoozed_until'),
            'snooze_count' => $event->snooze_count + 1,
        ]);

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'string']);

        foreach ($data['ids'] as $order => $id) {
            Event::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->update(['sort_order' => $order]);
        }

        return back();
    }

    private function recalculateMilestoneIfNeeded(Event $event): void
    {
        if (! $event->milestone_id) {
            return;
        }

        $milestone = Milestone::find($event->milestone_id);

        if (! $milestone) {
            return;
        }

        $milestone->recalculateDerivedDates();

        // Flag breach for hard deadline
        if ($milestone->deadline_type === DeadlineType::Hard && $event->end_at && $milestone->end_at && $event->end_at->greaterThan($milestone->end_at)) {
            session()->flash('breach_warning', "Event '{$event->title}' exceeds the hard deadline of milestone '{$milestone->title}'.");
        }
    }
}
