<?php

namespace App\Services;

use App\Models\DailyOutput;
use App\Models\OutputRestDay;
use Carbon\Carbon;

class StreakCalculator
{
    private int $userId;
    private Carbon $startDate;
    private Carbon $endDate;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->startDate = Carbon::parse(DailyOutput::TRACKING_START);
        $this->endDate = Carbon::parse(DailyOutput::TRACKING_END);
    }

    /**
     * Calculate streak stats.
     */
    public function calculate(): array
    {
        $today = Carbon::now('Asia/Tokyo')->startOfDay()->min($this->endDate);

        if ($today->lt($this->startDate)) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'rest_days_available' => 0,
                'rest_days_earned_total' => 0,
                'rest_days_used' => 0,
                'day_number' => 0,
                'total_days' => (int) $this->startDate->diffInDays($this->endDate) + 1,
            ];
        }

        // Get all dates with at least 1 "done" output
        $activeDates = DailyOutput::where('user_id', $this->userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$this->startDate->format('Y-m-d'), $today->format('Y-m-d')])
            ->selectRaw('DISTINCT output_date')
            ->pluck('output_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->flip()
            ->all();

        // Get dates that have only "skipped" outputs (no "done") — these don't break streak
        $skippedOnlyDates = DailyOutput::where('user_id', $this->userId)
            ->where('status', 'skipped')
            ->whereBetween('output_date', [$this->startDate->format('Y-m-d'), $today->format('Y-m-d')])
            ->selectRaw('DISTINCT output_date')
            ->pluck('output_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->filter(fn($d) => !isset($activeDates[$d]))
            ->flip()
            ->all();

        // Get all rest days
        $restDays = OutputRestDay::where('user_id', $this->userId)
            ->whereBetween('rest_date', [$this->startDate->format('Y-m-d'), $today->format('Y-m-d')])
            ->get()
            ->keyBy(fn($r) => $r->rest_date->format('Y-m-d'));

        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;
        $consecutiveForEarn = 0;
        $earnedRestDays = 0;
        $usedRestDays = $restDays->count();

        // Only count up to yesterday for streak breaks.
        // Today is still in progress, so don't penalize missing output yet.
        $yesterday = Carbon::now('Asia/Tokyo')->startOfDay()->subDay()->min($this->endDate);
        $cursor = $this->startDate->copy();

        while ($cursor->lte($yesterday)) {
            $dateStr = $cursor->format('Y-m-d');
            $hasOutput = isset($activeDates[$dateStr]);
            $isRestDay = $restDays->has($dateStr);

            $isSkippedOnly = isset($skippedOnlyDates[$dateStr]);

            if ($hasOutput) {
                $tempStreak++;
                $consecutiveForEarn++;

                // Every 7 consecutive days earns 1 rest day
                if ($consecutiveForEarn % 7 === 0) {
                    $earnedRestDays++;
                }
            } elseif ($isRestDay && $restDays->get($dateStr)->is_earned) {
                // Earned rest day: streak continues but doesn't count towards earning
                $tempStreak++;
            } elseif ($isSkippedOnly) {
                // Day with only skipped outputs: don't break streak, don't increment
                // (user acknowledged the day but couldn't complete)
            } else {
                // No output, no earned rest day → streak resets
                $longestStreak = max($longestStreak, $tempStreak);
                $tempStreak = 0;
                $consecutiveForEarn = 0;
            }

            $cursor->addDay();
        }

        // If today has output, count it toward the streak
        $todayStr = Carbon::now('Asia/Tokyo')->format('Y-m-d');
        if (isset($activeDates[$todayStr])) {
            $tempStreak++;
            $consecutiveForEarn++;
            if ($consecutiveForEarn % 7 === 0) {
                $earnedRestDays++;
            }
        }

        $currentStreak = $tempStreak;
        $longestStreak = max($longestStreak, $currentStreak);
        $restDaysAvailable = min(3, max(0, $earnedRestDays - $usedRestDays));

        $dayNumber = (int) $this->startDate->diffInDays($today) + 1;
        $totalDays = (int) $this->startDate->diffInDays($this->endDate) + 1;

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'rest_days_available' => $restDaysAvailable,
            'rest_days_earned_total' => $earnedRestDays,
            'rest_days_used' => $usedRestDays,
            'day_number' => $dayNumber,
            'total_days' => $totalDays,
        ];
    }

    /**
     * Get heatmap data for calendar view.
     */
    public function getHeatmapData(): array
    {
        $outputs = DailyOutput::where('user_id', $this->userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->selectRaw('output_date, COUNT(*) as count, SUM(duration) as total_duration')
            ->groupBy('output_date')
            ->get()
            ->keyBy(fn($o) => Carbon::parse($o->output_date)->format('Y-m-d'));

        $restDates = OutputRestDay::where('user_id', $this->userId)
            ->pluck('rest_date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->flip()
            ->all();

        $heatmap = [];
        $cursor = $this->startDate->copy();
        while ($cursor->lte($this->endDate)) {
            $dateStr = $cursor->format('Y-m-d');
            $dayData = $outputs->get($dateStr);

            $heatmap[] = [
                'date' => $dateStr,
                'count' => $dayData ? $dayData->count : 0,
                'duration' => $dayData ? (int) $dayData->total_duration : 0,
                'is_rest_day' => isset($restDates[$dateStr]),
            ];
            $cursor->addDay();
        }

        return $heatmap;
    }
}
