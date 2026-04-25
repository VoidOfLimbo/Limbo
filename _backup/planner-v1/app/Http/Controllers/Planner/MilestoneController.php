<?php

namespace App\Http\Controllers\Planner;

use App\Enums\DurationSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planner\StoreMilestoneRequest;
use App\Http\Requests\Planner\UpdateMilestoneRequest;
use App\Models\Milestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $milestones = Milestone::query()
            ->forUser($request->user()->id)
            ->orderBy('created_at')
            ->get()
            ->map(function (Milestone $milestone) {
                $milestone->setAttribute('progress', $milestone->progress);
                $milestone->setAttribute('is_breached', $milestone->isBreached());

                return $milestone;
            });

        return response()->json($milestones);
    }

    public function store(StoreMilestoneRequest $request): RedirectResponse
    {
        $request->user()->milestones()->create($request->validated());

        return back();
    }

    public function show(Request $request, Milestone $milestone): JsonResponse
    {
        $this->authorize('view', $milestone);

        $milestone->setAttribute('progress', $milestone->progress);
        $milestone->setAttribute('is_breached', $milestone->isBreached());

        return response()->json($milestone->load(['events', 'tags']));
    }

    public function update(UpdateMilestoneRequest $request, Milestone $milestone): RedirectResponse
    {
        $this->authorize('update', $milestone);

        $data = $request->validated();

        // Hard deadline end_at is immutable once set
        if ($milestone->deadline_type->value === 'hard' && $milestone->end_at !== null) {
            unset($data['end_at']);
        }

        $milestone->update($data);

        // Recalculate derived dates when set to derived mode
        if ($milestone->fresh()->duration_source === DurationSource::Derived) {
            $milestone->fresh()->recalculateDerivedDates();
        }

        return back();
    }

    public function destroy(Request $request, Milestone $milestone): RedirectResponse
    {
        $this->authorize('delete', $milestone);

        $milestone->delete();

        return back();
    }
}
