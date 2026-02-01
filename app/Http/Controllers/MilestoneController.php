<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Milestone;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    /**
     * Store a newly created milestone.
     */
    public function store(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_value' => 'nullable|numeric',
            'due_date' => 'nullable|date',
        ]);

        $validated['goal_id'] = $goal->id;
        $validated['sort_order'] = $goal->milestones()->count() + 1;

        $goal->milestones()->create($validated);

        return back()->with('success', 'Milestone added!');
    }

    /**
     * Update the specified milestone.
     */
    public function update(Request $request, Goal $goal, Milestone $milestone)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_value' => 'nullable|numeric',
            'due_date' => 'nullable|date',
        ]);

        $milestone->update($validated);

        return back()->with('success', 'Milestone updated!');
    }

    /**
     * Toggle milestone completion status.
     */
    public function toggle(Goal $goal, Milestone $milestone)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id) {
            abort(403);
        }

        if ($milestone->is_completed) {
            $milestone->markAsIncomplete();
        } else {
            $milestone->markAsCompleted();
        }

        return back();
    }

    /**
     * Reorder milestones.
     */
    public function reorder(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:milestones,id',
        ]);

        foreach ($validated['order'] as $index => $milestoneId) {
            Milestone::where('id', $milestoneId)
                ->where('goal_id', $goal->id)
                ->update(['sort_order' => $index + 1]);
        }

        return back();
    }

    /**
     * Remove the specified milestone.
     */
    public function destroy(Goal $goal, Milestone $milestone)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id) {
            abort(403);
        }

        $milestone->delete();

        // Re-calculate goal progress after milestone deletion
        $totalMilestones = $goal->milestones()->count();
        if ($totalMilestones > 0) {
            $completedMilestones = $goal->milestones()->where('is_completed', true)->count();
            $progress = (int) round(($completedMilestones / $totalMilestones) * 100);
            $goal->update(['progress' => $progress]);
        }

        return back()->with('success', 'Milestone deleted!');
    }
}
