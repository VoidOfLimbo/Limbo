<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Planner\UpsertPlannerFieldValueRequest;
use App\Models\Event;
use App\Models\Milestone;
use App\Models\PlannerField;
use App\Models\PlannerFieldValue;
use Illuminate\Http\RedirectResponse;

class PlannerFieldValueController extends Controller
{
    private const ALLOWED_ITEM_TYPES = [
        'event' => Event::class,
        'milestone' => Milestone::class,
    ];

    public function upsert(UpsertPlannerFieldValueRequest $request, PlannerField $plannerField, string $itemType, string $itemId): RedirectResponse
    {
        $this->authorize('view', $plannerField);

        $modelClass = self::ALLOWED_ITEM_TYPES[$itemType] ?? null;

        abort_if($modelClass === null, 422, 'Invalid item type.');

        $item = $modelClass::where('id', $itemId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        PlannerFieldValue::updateOrCreate(
            ['field_id' => $plannerField->id, 'item_id' => $item->id, 'item_type' => $modelClass],
            ['value' => $request->validated('value')],
        );

        return back();
    }

    public function destroy(PlannerField $plannerField, string $itemType, string $itemId): RedirectResponse
    {
        $this->authorize('view', $plannerField);

        $modelClass = self::ALLOWED_ITEM_TYPES[$itemType] ?? null;

        abort_if($modelClass === null, 422, 'Invalid item type.');

        PlannerFieldValue::where('field_id', $plannerField->id)
            ->where('item_id', $itemId)
            ->where('item_type', $modelClass)
            ->delete();

        return back();
    }
}
