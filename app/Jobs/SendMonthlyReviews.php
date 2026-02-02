<?php

namespace App\Jobs;

use App\Mail\MonthlyReviewMail;
use App\Models\ReviewSetting;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMonthlyReviews implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $settings = ReviewSetting::with(['user.goals.category', 'user.goals.progressLogs'])
            ->where('monthly_enabled', true)
            ->get();

        foreach ($settings as $setting) {
            if (!$setting->shouldSendMonthly()) {
                continue;
            }

            $user = $setting->user;

            if (!$user || !$user->email) {
                continue;
            }

            try {
                $monthData = $this->collectMonthlyData($user);
                Mail::to($user->email)->send(new MonthlyReviewMail($user, $monthData));
                $setting->update(['last_monthly_sent_at' => now()]);
            } catch (\Exception $e) {
                \Log::error("Failed to send monthly review for user {$user->id}: " . $e->getMessage());
            }
        }
    }

    private function collectMonthlyData($user): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $goals = $user->goals()->with(['category', 'progressLogs'])->get();

        // Goals completed this month
        $completedThisMonth = $goals->filter(function ($goal) use ($monthStart, $monthEnd) {
            if (!$goal->completed_at) {
                return false;
            }
            return Carbon::parse($goal->completed_at)->between($monthStart, $monthEnd);
        });

        // Goals completed last month (for comparison)
        $completedLastMonth = $goals->filter(function ($goal) use ($lastMonthStart, $lastMonthEnd) {
            if (!$goal->completed_at) {
                return false;
            }
            return Carbon::parse($goal->completed_at)->between($lastMonthStart, $lastMonthEnd);
        });

        // Calculate progress change this month
        $progressAtMonthStart = $this->calculateProgressAtDate($goals, $monthStart);
        $currentProgress = round($goals->avg('progress'));
        $progressChange = $currentProgress - $progressAtMonthStart;

        // Best performing category
        $categoryStats = [];
        $categories = $goals->pluck('category')->unique('id');
        foreach ($categories as $category) {
            if (!$category) continue;
            $categoryGoals = $goals->where('category_id', $category->id);
            if ($categoryGoals->count() > 0) {
                $categoryStats[] = [
                    'category' => $category,
                    'avg_progress' => round($categoryGoals->avg('progress')),
                    'count' => $categoryGoals->count(),
                ];
            }
        }
        usort($categoryStats, fn($a, $b) => $b['avg_progress'] <=> $a['avg_progress']);
        $bestCategory = count($categoryStats) > 0 ? $categoryStats[0] : null;

        // Goals needing attention (low progress + deadline approaching)
        $needsAttention = $goals->filter(function ($goal) {
            if ($goal->status === 'completed' || $goal->status === 'cancelled') {
                return false;
            }
            // Low progress AND has deadline within 30 days
            if ($goal->progress < 25 && $goal->target_date) {
                $daysUntilDeadline = Carbon::now()->diffInDays(Carbon::parse($goal->target_date), false);
                return $daysUntilDeadline >= 0 && $daysUntilDeadline <= 30;
            }
            return false;
        });

        // Overall statistics
        $totalGoals = $goals->count();
        $completedGoals = $goals->where('status', 'completed')->count();
        $completionRate = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100) : 0;

        return [
            'completed_this_month' => $completedThisMonth->values(),
            'completed_last_month_count' => $completedLastMonth->count(),
            'progress_change' => $progressChange,
            'current_progress' => $currentProgress,
            'best_category' => $bestCategory,
            'needs_attention' => $needsAttention->values(),
            'total_goals' => $totalGoals,
            'completed_goals' => $completedGoals,
            'completion_rate' => $completionRate,
            'month_name' => Carbon::now()->format('F Y'),
        ];
    }

    private function calculateProgressAtDate($goals, Carbon $date): int
    {
        $totalProgress = 0;
        $goalCount = 0;

        foreach ($goals as $goal) {
            // Find the most recent progress log before the date
            $log = $goal->progressLogs
                ->filter(fn($l) => Carbon::parse($l->logged_at)->lt($date))
                ->sortByDesc('logged_at')
                ->first();

            if ($log) {
                $totalProgress += $log->new_progress;
            }
            // If no log before date, assume 0
            $goalCount++;
        }

        return $goalCount > 0 ? round($totalProgress / $goalCount) : 0;
    }
}
