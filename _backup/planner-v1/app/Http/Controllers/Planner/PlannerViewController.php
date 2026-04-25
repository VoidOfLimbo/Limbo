<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Planner\StorePlannerViewRequest;
use App\Http\Requests\Planner\UpdatePlannerViewRequest;
use App\Models\PlannerView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlannerViewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $views = PlannerView::query()
            ->where('user_id', $request->user()->id)
            ->when($request->query('milestone_id'), fn ($q, $id) => $q->where('milestone_id', $id))
            ->orderBy('position')
            ->get();

        return response()->json($views);
    }

    public function store(StorePlannerViewRequest $request): RedirectResponse
    {
        $this->authorize('create', PlannerView::class);

        $request->user()->plannerViews()->create($request->validated());

        return back();
    }

    public function update(UpdatePlannerViewRequest $request, PlannerView $plannerView): RedirectResponse
    {
        $this->authorize('update', $plannerView);

        $plannerView->update($request->validated());

        return back();
    }

    public function destroy(PlannerView $plannerView): RedirectResponse
    {
        $this->authorize('delete', $plannerView);

        $plannerView->delete();

        return back();
    }

    public function activate(Request $request, PlannerView $plannerView): RedirectResponse
    {
        $this->authorize('update', $plannerView);

        // Clear the existing default for this user + milestone scope
        PlannerView::where('user_id', $request->user()->id)
            ->where('milestone_id', $plannerView->milestone_id)
            ->update(['is_default' => false]);

        $plannerView->update(['is_default' => true]);

        return back();
    }
}
