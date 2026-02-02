<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    /**
     * Store a new reminder for a goal.
     */
    public function store(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'type' => 'required|in:deadline,progress,custom',
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'custom_days' => 'nullable|string',
            'remind_time' => 'required|date_format:H:i',
            'message' => 'nullable|string|max:500',
        ]);

        $validated['goal_id'] = $goal->id;
        $validated['is_active'] = true;

        $reminder = Reminder::create($validated);
        $reminder->calculateNextSendAt();

        return back()->with('success', 'Reminder created successfully!');
    }

    /**
     * Update a reminder.
     */
    public function update(Request $request, Goal $goal, Reminder $reminder)
    {
        $this->authorize('update', $goal);

        if ($reminder->goal_id !== $goal->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:deadline,progress,custom',
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'custom_days' => 'nullable|string',
            'remind_time' => 'required|date_format:H:i',
            'message' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $reminder->update($validated);

        if ($reminder->is_active) {
            $reminder->calculateNextSendAt();
        }

        return back()->with('success', 'Reminder updated successfully!');
    }

    /**
     * Toggle reminder active status.
     */
    public function toggle(Goal $goal, Reminder $reminder)
    {
        $this->authorize('update', $goal);

        if ($reminder->goal_id !== $goal->id) {
            abort(403);
        }

        $reminder->update(['is_active' => !$reminder->is_active]);

        if ($reminder->is_active) {
            $reminder->calculateNextSendAt();
        }

        return back();
    }

    /**
     * Delete a reminder.
     */
    public function destroy(Goal $goal, Reminder $reminder)
    {
        $this->authorize('update', $goal);

        if ($reminder->goal_id !== $goal->id) {
            abort(403);
        }

        $reminder->delete();

        return back()->with('success', 'Reminder deleted successfully!');
    }
}
