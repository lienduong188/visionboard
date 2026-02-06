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
            'frequency' => 'required|in:daily,weekly,monthly,specific',
            'specific_dates' => 'nullable|string', // e.g., "2026-03-15,2026-05-20"
            'weekly_days' => 'nullable|string', // e.g., "1,3,5" for Mon, Wed, Fri
            'monthly_day' => 'nullable|integer|min:1|max:31', // day of month
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
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
            'frequency' => 'required|in:daily,weekly,monthly,specific',
            'specific_dates' => 'nullable|string',
            'weekly_days' => 'nullable|string',
            'monthly_day' => 'nullable|integer|min:1|max:31',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
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
     * Dismiss reminder for today (skip current notification).
     */
    public function dismiss(Goal $goal, Reminder $reminder)
    {
        $this->authorize('update', $goal);

        if ($reminder->goal_id !== $goal->id) {
            abort(403);
        }

        // Mark as sent to calculate next_send_at
        $reminder->markAsSent();

        // If next_send_at is still today, force it to tomorrow
        // This handles the case where remind_time hasn't passed yet
        if ($reminder->next_send_at && $reminder->next_send_at->isToday()) {
            $reminder->update([
                'next_send_at' => $reminder->next_send_at->addDay()
            ]);
        }

        // Redirect to today page to refresh data
        return redirect()->route('today.index')->with('success', 'Reminder dismissed for today!');
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
