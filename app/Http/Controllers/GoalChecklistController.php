<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\GoalChecklist;
use Illuminate\Http\Request;

class GoalChecklistController extends Controller
{
    /**
     * Store a newly created checklist item.
     */
    public function store(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $validated['sort_order'] = $goal->checklists()->count() + 1;

        $goal->checklists()->create($validated);

        return back()->with('success', 'Checklist item added!');
    }

    /**
     * Update the specified checklist item.
     */
    public function update(Request $request, Goal $goal, GoalChecklist $checklist)
    {
        $this->authorize('update', $goal);

        if ($checklist->goal_id !== $goal->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $checklist->update($validated);

        return back()->with('success', 'Checklist item updated!');
    }

    /**
     * Toggle checklist item completion status.
     */
    public function toggle(Goal $goal, GoalChecklist $checklist)
    {
        $this->authorize('update', $goal);

        if ($checklist->goal_id !== $goal->id) {
            abort(403);
        }

        $checklist->update(['is_completed' => !$checklist->is_completed]);

        return back();
    }

    /**
     * Reorder checklist items.
     */
    public function reorder(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:goal_checklists,id',
        ]);

        foreach ($validated['order'] as $index => $checklistId) {
            GoalChecklist::where('id', $checklistId)
                ->where('goal_id', $goal->id)
                ->update(['sort_order' => $index + 1]);
        }

        return back();
    }

    /**
     * Remove the specified checklist item.
     */
    public function destroy(Goal $goal, GoalChecklist $checklist)
    {
        $this->authorize('update', $goal);

        if ($checklist->goal_id !== $goal->id) {
            abort(403);
        }

        $checklist->delete();

        return back()->with('success', 'Checklist item deleted!');
    }
}
