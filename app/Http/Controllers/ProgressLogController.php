<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\ProgressLog;
use Illuminate\Http\Request;

class ProgressLogController extends Controller
{
    /**
     * Store a new progress log (with custom date).
     */
    public function store(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'new_value' => 'required|numeric',
            'logged_at' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        // Calculate progress
        $newProgress = $goal->calculateProgressForValue((float) $validated['new_value']);

        // Get previous value (for this date or earlier)
        $previousLog = $goal->progressLogs()
            ->where('logged_at', '<', $validated['logged_at'])
            ->orderBy('logged_at', 'desc')
            ->first();

        $previousValue = $previousLog?->new_value ?? $goal->start_value ?? 0;
        $previousProgress = $previousLog?->new_progress ?? 0;

        // Create the log
        $log = $goal->progressLogs()->create([
            'previous_value' => $previousValue,
            'new_value' => $validated['new_value'],
            'previous_progress' => $previousProgress,
            'new_progress' => $newProgress,
            'note' => $validated['note'] ?? null,
            'logged_at' => $validated['logged_at'],
        ]);

        // Update goal's current_value if this is the latest log
        $latestLog = $goal->progressLogs()->orderBy('logged_at', 'desc')->first();
        if ($latestLog && $latestLog->id === $log->id) {
            $goal->current_value = $validated['new_value'];
            $goal->progress = $newProgress;
            $goal->save();
        }

        return back()->with('success', 'Progress log added!');
    }

    /**
     * Update an existing progress log.
     */
    public function update(Request $request, Goal $goal, ProgressLog $progressLog)
    {
        $this->authorize('update', $goal);

        // Verify the log belongs to the goal
        if ($progressLog->goal_id !== $goal->id) {
            abort(404);
        }

        $validated = $request->validate([
            'new_value' => 'required|numeric',
            'logged_at' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        // Recalculate progress
        $newProgress = $goal->calculateProgressForValue((float) $validated['new_value']);

        $progressLog->update([
            'new_value' => $validated['new_value'],
            'new_progress' => $newProgress,
            'note' => $validated['note'] ?? null,
            'logged_at' => $validated['logged_at'],
        ]);

        // Update goal's current_value if this is the latest log
        $latestLog = $goal->progressLogs()->orderBy('logged_at', 'desc')->first();
        if ($latestLog && $latestLog->id === $progressLog->id) {
            $goal->current_value = $validated['new_value'];
            $goal->progress = $newProgress;
            $goal->save();
        }

        return back()->with('success', 'Progress log updated!');
    }

    /**
     * Delete a progress log.
     */
    public function destroy(Goal $goal, ProgressLog $progressLog)
    {
        $this->authorize('update', $goal);

        // Verify the log belongs to the goal
        if ($progressLog->goal_id !== $goal->id) {
            abort(404);
        }

        $wasLatest = $goal->progressLogs()->orderBy('logged_at', 'desc')->first()?->id === $progressLog->id;

        $progressLog->delete();

        // If deleted the latest log, update goal's current_value from new latest
        if ($wasLatest) {
            $newLatest = $goal->progressLogs()->orderBy('logged_at', 'desc')->first();
            if ($newLatest) {
                $goal->current_value = $newLatest->new_value;
                $goal->progress = $newLatest->new_progress;
            } else {
                // No logs left, reset to start value
                $goal->current_value = $goal->start_value ?? 0;
                $goal->progress = $goal->calculateProgress();
            }
            $goal->save();
        }

        return back()->with('success', 'Progress log deleted!');
    }
}
