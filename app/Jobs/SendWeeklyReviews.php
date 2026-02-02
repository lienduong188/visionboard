<?php

namespace App\Jobs;

use App\Mail\WeeklyReviewMail;
use App\Models\ReviewSetting;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReviews implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $settings = ReviewSetting::with(['user.goals.category', 'user.goals.progressLogs'])
            ->where('weekly_enabled', true)
            ->get();

        foreach ($settings as $setting) {
            if (!$setting->shouldSendWeekly()) {
                continue;
            }

            $user = $setting->user;

            if (!$user || !$user->email) {
                continue;
            }

            try {
                $weekData = $this->collectWeeklyData($user);
                Mail::to($user->email)->send(new WeeklyReviewMail($user, $weekData));
                $setting->update(['last_weekly_sent_at' => now()]);
            } catch (\Exception $e) {
                \Log::error("Failed to send weekly review for user {$user->id}: " . $e->getMessage());
            }
        }
    }

    private function collectWeeklyData($user): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $goals = $user->goals()->with(['category', 'progressLogs'])->get();

        // Goals with progress this week
        $goalsWithProgress = $goals->filter(function ($goal) use ($weekStart, $weekEnd) {
            return $goal->progressLogs->filter(function ($log) use ($weekStart, $weekEnd) {
                $logDate = Carbon::parse($log->logged_at);
                return $logDate->between($weekStart, $weekEnd);
            })->count() > 0;
        });

        // Goals without progress this week (stalled)
        $stalledGoals = $goals->filter(function ($goal) use ($weekStart, $weekEnd, $goalsWithProgress) {
            return !in_array($goal->status, ['completed', 'cancelled']) &&
                !$goalsWithProgress->contains('id', $goal->id);
        });

        // Goals completed this week
        $completedThisWeek = $goals->filter(function ($goal) use ($weekStart, $weekEnd) {
            if (!$goal->completed_at) {
                return false;
            }
            return Carbon::parse($goal->completed_at)->between($weekStart, $weekEnd);
        });

        // Goals with upcoming deadlines (next 7 days)
        $upcomingDeadlines = $goals->filter(function ($goal) {
            if (!$goal->target_date || $goal->status === 'completed') {
                return false;
            }
            $targetDate = Carbon::parse($goal->target_date);
            return $targetDate->between(Carbon::now(), Carbon::now()->addDays(7));
        });

        // Overall statistics
        $totalGoals = $goals->count();
        $overallProgress = $totalGoals > 0 ? round($goals->avg('progress')) : 0;

        return [
            'goals_with_progress' => $goalsWithProgress->values(),
            'stalled_goals' => $stalledGoals->values(),
            'completed_this_week' => $completedThisWeek->values(),
            'upcoming_deadlines' => $upcomingDeadlines->values(),
            'total_goals' => $totalGoals,
            'overall_progress' => $overallProgress,
            'week_start' => $weekStart->format('M j'),
            'week_end' => $weekEnd->format('M j, Y'),
        ];
    }
}
