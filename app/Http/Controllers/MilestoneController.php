<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'memo' => 'nullable|string',
            'target_value' => 'nullable|numeric',
            'due_date' => 'nullable|date',
            'is_soft' => 'boolean',
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
            'memo' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'target_value' => 'nullable|numeric',
            'due_date' => 'nullable|date',
            'is_soft' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($milestone->image_path) {
                Storage::disk('public')->delete($milestone->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('milestones', 'public');
        } elseif ($request->boolean('remove_image') && $milestone->image_path) {
            Storage::disk('public')->delete($milestone->image_path);
            $validated['image_path'] = null;
        }

        // Remove non-model fields
        unset($validated['image'], $validated['remove_image']);

        $milestone->update($validated);

        // Update goal progress (in case is_soft changed)
        $this->recalculateGoalProgress($goal);

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
     * Toggle milestone soft status (reminder vs real milestone).
     */
    public function toggleSoft(Goal $goal, Milestone $milestone)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id) {
            abort(403);
        }

        $milestone->update(['is_soft' => !$milestone->is_soft]);

        // Recalculate goal progress (soft milestones are excluded)
        $this->recalculateGoalProgress($goal);

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

        // Delete image if exists
        if ($milestone->image_path) {
            Storage::disk('public')->delete($milestone->image_path);
        }

        $milestone->delete();

        // Re-calculate goal progress after milestone deletion (excluding soft milestones)
        $this->recalculateGoalProgress($goal);

        return back()->with('success', 'Milestone deleted!');
    }

    /**
     * Recalculate goal progress based on non-soft milestones.
     */
    private function recalculateGoalProgress(Goal $goal): void
    {
        $totalMilestones = $goal->milestones()->where('is_soft', false)->count();

        if ($totalMilestones > 0) {
            $completedMilestones = $goal->milestones()
                ->where('is_soft', false)
                ->where('is_completed', true)
                ->count();
            $progress = (int) round(($completedMilestones / $totalMilestones) * 100);
            $goal->update(['progress' => $progress]);
        }
    }
}
