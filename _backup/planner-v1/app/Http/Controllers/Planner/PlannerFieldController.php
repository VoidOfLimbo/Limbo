<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Planner\StorePlannerFieldRequest;
use App\Http\Requests\Planner\UpdatePlannerFieldRequest;
use App\Models\PlannerField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlannerFieldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $milestoneId = $request->query('milestone_id');

        $fields = PlannerField::query()
            ->where('user_id', $request->user()->id)
            ->forMilestone($milestoneId)
            ->orderBy('position')
            ->get();

        return response()->json($fields);
    }

    public function store(StorePlannerFieldRequest $request): RedirectResponse
    {
        $this->authorize('create', PlannerField::class);

        $request->user()->plannerFields()->create($request->validated());

        return back();
    }

    public function update(UpdatePlannerFieldRequest $request, PlannerField $plannerField): RedirectResponse
    {
        $this->authorize('update', $plannerField);

        $plannerField->update($request->validated());

        return back();
    }

    public function destroy(PlannerField $plannerField): RedirectResponse
    {
        $this->authorize('delete', $plannerField);

        $plannerField->delete();

        return back();
    }

    public function storeOption(Request $request, PlannerField $plannerField): RedirectResponse
    {
        $this->authorize('update', $plannerField);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $options = $plannerField->options ?? [];
        $options[] = ['id' => (string) Str::ulid(), 'name' => $data['name'], 'color' => $data['color'] ?? null];

        $plannerField->update(['options' => $options]);

        return back();
    }

    public function destroyOption(Request $request, PlannerField $plannerField, string $optionId): RedirectResponse
    {
        $this->authorize('update', $plannerField);

        $options = collect($plannerField->options ?? [])
            ->reject(fn ($opt) => $opt['id'] === $optionId)
            ->values()
            ->all();

        $plannerField->update(['options' => $options]);

        return back();
    }
}
