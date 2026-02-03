<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\MilestoneTodo;
use Illuminate\Http\Request;

class MilestoneTodoController extends Controller
{
    /**
     * Store a newly created todo.
     */
    public function store(Request $request, Goal $goal, Milestone $milestone)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['sort_order'] = $milestone->todos()->count() + 1;

        $milestone->todos()->create($validated);

        return back()->with('success', 'Todo added!');
    }

    /**
     * Update the specified todo.
     */
    public function update(Request $request, Goal $goal, Milestone $milestone, MilestoneTodo $todo)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id || $todo->milestone_id !== $milestone->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $todo->update($validated);

        return back()->with('success', 'Todo updated!');
    }

    /**
     * Toggle todo completion status.
     */
    public function toggle(Goal $goal, Milestone $milestone, MilestoneTodo $todo)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id || $todo->milestone_id !== $milestone->id) {
            abort(403);
        }

        if ($todo->is_completed) {
            $todo->markAsIncomplete();
        } else {
            $todo->markAsCompleted();
        }

        return back();
    }

    /**
     * Reorder todos.
     */
    public function reorder(Request $request, Goal $goal, Milestone $milestone)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id) {
            abort(403);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:milestone_todos,id',
        ]);

        foreach ($validated['order'] as $index => $todoId) {
            MilestoneTodo::where('id', $todoId)
                ->where('milestone_id', $milestone->id)
                ->update(['sort_order' => $index + 1]);
        }

        return back();
    }

    /**
     * Remove the specified todo.
     */
    public function destroy(Goal $goal, Milestone $milestone, MilestoneTodo $todo)
    {
        $this->authorize('update', $goal);

        if ($milestone->goal_id !== $goal->id || $todo->milestone_id !== $milestone->id) {
            abort(403);
        }

        $todo->delete();

        return back()->with('success', 'Todo deleted!');
    }
}
