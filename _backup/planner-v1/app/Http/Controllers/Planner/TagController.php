<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Planner\AttachTagRequest;
use App\Http\Requests\Planner\StoreTagRequest;
use App\Http\Requests\Planner\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tags = Tag::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $request->user()->tags()->create($request->validated());

        return back();
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $tag);

        $tag->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return back();
    }

    public function attach(AttachTagRequest $request, Tag $tag): RedirectResponse
    {
        $this->authorize('attach', $tag);

        $modelClass = $request->taggableModelClass();
        $taggable = $modelClass::findOrFail($request->validated('taggable_id'));

        $taggable->tags()->attach($tag->id);

        return back();
    }

    public function detach(AttachTagRequest $request, Tag $tag): RedirectResponse
    {
        $this->authorize('detach', $tag);

        $modelClass = $request->taggableModelClass();
        $taggable = $modelClass::findOrFail($request->validated('taggable_id'));

        // Use the polymorphic relationship to detach correctly
        if ($request->validated('taggable_type') === 'event') {
            $tag->events()->detach($taggable->id);
        } else {
            $tag->milestones()->detach($taggable->id);
        }

        return back();
    }
}
