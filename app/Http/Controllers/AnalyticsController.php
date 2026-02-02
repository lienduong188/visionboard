<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Goal;
use App\Models\ProgressLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $goals = Goal::with(['category', 'progressLogs'])
            ->where('user_id', $user->id)
            ->get();

        $categories = Category::ordered()->get();

        return Inertia::render('Analytics/Index', [
            'completionTrend' => $this->getCompletionTrend($user, $goals),
            'categoryComparison' => $this->getCategoryComparison($goals, $categories),
            'monthlyCompletion' => $this->getMonthlyCompletion($goals),
            'detailedStats' => $this->getDetailedStats($goals, $categories),
            'categories' => $categories,
        ]);
    }

    /**
     * Get completion trend data over time.
     * Shows cumulative completed goals and overall progress trend.
     */
    private function getCompletionTrend($user, $goals): array
    {
        // Get all progress logs from the past 90 days
        $startDate = Carbon::now()->subDays(90)->startOfDay();

        $progressLogs = ProgressLog::whereIn('goal_id', $goals->pluck('id'))
            ->where('logged_at', '>=', $startDate)
            ->orderBy('logged_at')
            ->get();

        // Group logs by date
        $logsByDate = $progressLogs->groupBy(function ($log) {
            return Carbon::parse($log->logged_at)->format('Y-m-d');
        });

        // Build daily data for each period
        $periods = [7, 30, 90];
        $result = [];

        foreach ($periods as $days) {
            $labels = [];
            $progressData = [];
            $completedData = [];

            $periodStart = Carbon::now()->subDays($days)->startOfDay();
            $currentDate = $periodStart->copy();

            // Track completed goals count over time
            $completedGoals = $goals->filter(function ($goal) use ($periodStart) {
                return $goal->completed_at && Carbon::parse($goal->completed_at) < $periodStart;
            })->count();

            while ($currentDate <= Carbon::now()) {
                $dateStr = $currentDate->format('Y-m-d');
                $labels[] = $currentDate->format('M j');

                // Check if any goal was completed on this date
                $newlyCompleted = $goals->filter(function ($goal) use ($dateStr) {
                    return $goal->completed_at &&
                        Carbon::parse($goal->completed_at)->format('Y-m-d') === $dateStr;
                })->count();

                $completedGoals += $newlyCompleted;
                $completedData[] = $completedGoals;

                // Calculate average progress for this date based on logs
                if (isset($logsByDate[$dateStr])) {
                    $dayLogs = $logsByDate[$dateStr];
                    $avgProgress = round($dayLogs->avg('new_progress'));
                    $progressData[] = $avgProgress;
                } else {
                    // Use previous value or calculate current average
                    $progressData[] = count($progressData) > 0
                        ? end($progressData)
                        : round($goals->avg('progress'));
                }

                $currentDate->addDay();
            }

            $result[$days] = [
                'labels' => $labels,
                'progress' => $progressData,
                'completed' => $completedData,
            ];
        }

        return $result;
    }

    /**
     * Get category comparison data.
     * Shows average progress and goal count per category.
     */
    private function getCategoryComparison($goals, $categories): array
    {
        $comparison = [];

        foreach ($categories as $category) {
            $categoryGoals = $goals->where('category_id', $category->id);
            $goalCount = $categoryGoals->count();

            $comparison[] = [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'goalCount' => $goalCount,
                'avgProgress' => $goalCount > 0 ? round($categoryGoals->avg('progress')) : 0,
                'completedCount' => $categoryGoals->where('status', 'completed')->count(),
            ];
        }

        return $comparison;
    }

    /**
     * Get monthly completion data for the year.
     */
    private function getMonthlyCompletion($goals): array
    {
        $year = Carbon::now()->year;
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

            $completedInMonth = $goals->filter(function ($goal) use ($startOfMonth, $endOfMonth) {
                if (!$goal->completed_at) {
                    return false;
                }
                $completedAt = Carbon::parse($goal->completed_at);
                return $completedAt >= $startOfMonth && $completedAt <= $endOfMonth;
            })->count();

            $monthlyData[] = [
                'month' => $startOfMonth->format('M'),
                'monthFull' => $startOfMonth->format('F'),
                'completed' => $completedInMonth,
            ];
        }

        return $monthlyData;
    }

    /**
     * Get detailed statistics.
     */
    private function getDetailedStats($goals, $categories): array
    {
        $totalGoals = $goals->count();
        $completedGoals = $goals->where('status', 'completed')->count();
        $inProgressGoals = $goals->where('status', 'in_progress')->count();
        $notStartedGoals = $goals->where('status', 'not_started')->count();

        // Calculate completion rate
        $completionRate = $totalGoals > 0
            ? round(($completedGoals / $totalGoals) * 100)
            : 0;

        // Average progress
        $avgProgress = $totalGoals > 0 ? round($goals->avg('progress')) : 0;

        // Goals with upcoming deadlines (next 7 days)
        $upcomingDeadlines = $goals->filter(function ($goal) {
            if (!$goal->target_date || $goal->status === 'completed') {
                return false;
            }
            $targetDate = Carbon::parse($goal->target_date);
            return $targetDate->isBetween(Carbon::now(), Carbon::now()->addDays(7));
        })->count();

        // Overdue goals
        $overdueGoals = $goals->filter(function ($goal) {
            if (!$goal->target_date || $goal->status === 'completed') {
                return false;
            }
            return Carbon::parse($goal->target_date)->isPast();
        })->count();

        // Best performing category
        $categoryStats = [];
        foreach ($categories as $category) {
            $categoryGoals = $goals->where('category_id', $category->id);
            if ($categoryGoals->count() > 0) {
                $categoryStats[] = [
                    'category' => $category,
                    'avgProgress' => $categoryGoals->avg('progress'),
                    'count' => $categoryGoals->count(),
                ];
            }
        }

        usort($categoryStats, function ($a, $b) {
            return $b['avgProgress'] <=> $a['avgProgress'];
        });

        $bestCategory = count($categoryStats) > 0 ? $categoryStats[0] : null;
        $worstCategory = count($categoryStats) > 1 ? end($categoryStats) : null;

        return [
            'totalGoals' => $totalGoals,
            'completedGoals' => $completedGoals,
            'inProgressGoals' => $inProgressGoals,
            'notStartedGoals' => $notStartedGoals,
            'completionRate' => $completionRate,
            'avgProgress' => $avgProgress,
            'upcomingDeadlines' => $upcomingDeadlines,
            'overdueGoals' => $overdueGoals,
            'bestCategory' => $bestCategory ? [
                'name' => $bestCategory['category']->name,
                'icon' => $bestCategory['category']->icon,
                'avgProgress' => round($bestCategory['avgProgress']),
            ] : null,
            'worstCategory' => $worstCategory ? [
                'name' => $worstCategory['category']->name,
                'icon' => $worstCategory['category']->icon,
                'avgProgress' => round($worstCategory['avgProgress']),
            ] : null,
        ];
    }
}
