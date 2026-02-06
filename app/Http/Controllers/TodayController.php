<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodayController extends Controller
{
    /**
     * Display the Today view with tasks due today and upcoming.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        $endOfWeek = Carbon::today()->addDays(7);

        // Get user's goal IDs for filtering
        $goalIds = $user->goals()->pluck('id');

        // 1. Milestones with due_date (pending, within 7 days or overdue)
        $milestones = Milestone::with(['goal.category', 'todos'])
            ->whereIn('goal_id', $goalIds)
            ->where('is_completed', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $endOfWeek)
            ->orderBy('due_date')
            ->get()
            ->map(fn($m) => $this->formatMilestone($m, $today));

        // 2. Reminders due today or upcoming (active, within 7 days)
        $reminders = Reminder::with(['goal.category'])
            ->whereIn('goal_id', $goalIds)
            ->where('is_active', true)
            ->whereNotNull('next_send_at')
            ->where('next_send_at', '<=', $endOfWeek->endOfDay())
            ->orderBy('next_send_at')
            ->get()
            ->map(fn($r) => $this->formatReminder($r, $today));

        // Group all items by timeframe
        $grouped = $this->groupByTimeframe(
            $milestones->concat($reminders),
            $today
        );

        // Calculate stats
        $stats = [
            'overdue' => $grouped['overdue']->count(),
            'today' => $grouped['today']->count(),
            'upcoming' => $grouped['upcoming']->count(),
            'total' => $grouped['overdue']->count() +
                       $grouped['today']->count() +
                       $grouped['upcoming']->count(),
        ];

        return Inertia::render('Today/Index', [
            'items' => [
                'overdue' => $grouped['overdue']->values(),
                'today' => $grouped['today']->values(),
                'upcoming' => $grouped['upcoming']->values(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Format milestone for frontend.
     */
    private function formatMilestone(Milestone $milestone, Carbon $today): array
    {
        $dueDate = Carbon::parse($milestone->due_date);

        return [
            'id' => $milestone->id,
            'type' => 'milestone',
            'title' => $milestone->title,
            'description' => $milestone->description,
            'is_soft' => $milestone->is_soft,
            'is_completed' => $milestone->is_completed,
            'due_date' => $milestone->due_date->format('Y-m-d'),
            'date_for_sort' => $dueDate->timestamp,
            'is_overdue' => $dueDate->lt($today),
            'is_today' => $dueDate->isToday(),
            'goal' => [
                'id' => $milestone->goal->id,
                'title' => $milestone->goal->title,
                'category' => $milestone->goal->category,
            ],
            'todos_count' => $milestone->todos->count(),
            'todos_completed' => $milestone->todos->where('is_completed', true)->count(),
            'todos' => $milestone->todos->map(fn($todo) => [
                'id' => $todo->id,
                'title' => $todo->title,
                'is_completed' => $todo->is_completed,
                'end_date' => $todo->end_date?->format('Y-m-d'),
            ])->values()->all(),
        ];
    }

    /**
     * Format reminder for frontend.
     */
    private function formatReminder(Reminder $reminder, Carbon $today): array
    {
        $nextSend = Carbon::parse($reminder->next_send_at);

        return [
            'id' => $reminder->id,
            'type' => 'reminder',
            'title' => $reminder->message ?: $this->getReminderTypeLabel($reminder->type),
            'description' => $reminder->message,
            'reminder_type' => $reminder->type,
            'frequency' => $reminder->frequency,
            'remind_time' => $reminder->remind_time ? Carbon::parse($reminder->remind_time)->format('H:i') : null,
            'weekly_days' => $reminder->weekly_days,
            'monthly_day' => $reminder->monthly_day,
            'specific_dates' => $reminder->specific_dates,
            'start_date' => $reminder->start_date?->format('Y-m-d'),
            'end_date' => $reminder->end_date?->format('Y-m-d'),
            'is_completed' => false, // Reminders don't have completion
            'due_date' => $nextSend->format('Y-m-d'),
            'date_for_sort' => $nextSend->timestamp,
            'is_overdue' => $nextSend->lt($today),
            'is_today' => $nextSend->isToday(),
            'goal' => [
                'id' => $reminder->goal->id,
                'title' => $reminder->goal->title,
                'category' => $reminder->goal->category,
            ],
        ];
    }

    /**
     * Get human-readable label for reminder type.
     */
    private function getReminderTypeLabel(string $type): string
    {
        return match($type) {
            'progress' => 'Update Progress',
            'deadline' => 'Deadline Reminder',
            'custom' => 'Custom Reminder',
            default => 'Reminder',
        };
    }

    /**
     * Group items by timeframe: overdue, today, upcoming.
     */
    private function groupByTimeframe($items, Carbon $today): array
    {
        $overdue = collect();
        $todayItems = collect();
        $upcoming = collect();

        foreach ($items as $item) {
            if ($item['is_overdue']) {
                $overdue->push($item);
            } elseif ($item['is_today']) {
                $todayItems->push($item);
            } else {
                $upcoming->push($item);
            }
        }

        // Sort each group by date
        return [
            'overdue' => $overdue->sortBy('date_for_sort'),
            'today' => $todayItems->sortBy('date_for_sort'),
            'upcoming' => $upcoming->sortBy('date_for_sort'),
        ];
    }
}
