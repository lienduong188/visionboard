<?php

namespace App\Http\Controllers;

use App\Models\DailyOutput;
use App\Models\OutputRestDay;
use App\Services\StreakCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DailyOutputController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $view = $request->get('view', 'list');
        $date = $request->get('date', Carbon::now('Asia/Tokyo')->format('Y-m-d'));

        // Get all outputs within tracking period
        $allOutputs = DailyOutput::with('goal:id,title,category_id', 'goal.category:id,name,icon,color')
            ->where('user_id', $user->id)
            ->whereBetween('output_date', [DailyOutput::TRACKING_START, DailyOutput::TRACKING_END])
            ->orderBy('output_date', 'desc')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('output_date');

        // Streak & stats
        $calculator = new StreakCalculator($user->id);
        $streakData = $calculator->calculate();
        $heatmap = $calculator->getHeatmapData();

        // General stats
        $stats = $this->getStats($user->id);

        // User goals for linking
        $goals = $user->goals()
            ->select('id', 'title', 'category_id')
            ->with('category:id,name,icon')
            ->active()
            ->orderBy('title')
            ->get();

        // Rest days
        $restDays = OutputRestDay::where('user_id', $user->id)
            ->whereBetween('rest_date', [DailyOutput::TRACKING_START, DailyOutput::TRACKING_END])
            ->pluck('rest_date')
            ->map(fn($d) => $d->format('Y-m-d'));

        return Inertia::render('TrackingOutput/Index', [
            'outputs' => $allOutputs,
            'streakData' => $streakData,
            'stats' => $stats,
            'heatmap' => $heatmap,
            'goals' => $goals,
            'restDays' => $restDays,
            'currentDate' => $date,
            'currentView' => $view,
            'categories' => DailyOutput::CATEGORIES,
            'durationPresets' => DailyOutput::DURATION_PRESETS,
        ]);
    }

    public function publicIndex(Request $request)
    {
        // Personal app - get the first user's data publicly
        $user = \App\Models\User::first();
        if (!$user) abort(404);

        $view = $request->get('view', 'list');
        $date = $request->get('date', Carbon::now('Asia/Tokyo')->format('Y-m-d'));

        $allOutputs = DailyOutput::with('goal:id,title,category_id', 'goal.category:id,name,icon,color')
            ->where('user_id', $user->id)
            ->whereBetween('output_date', [DailyOutput::TRACKING_START, DailyOutput::TRACKING_END])
            ->orderBy('output_date', 'desc')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('output_date');

        $calculator = new StreakCalculator($user->id);
        $streakData = $calculator->calculate();
        $heatmap = $calculator->getHeatmapData();
        $stats = $this->getStats($user->id);

        $restDays = OutputRestDay::where('user_id', $user->id)
            ->whereBetween('rest_date', [DailyOutput::TRACKING_START, DailyOutput::TRACKING_END])
            ->pluck('rest_date')
            ->map(fn($d) => $d->format('Y-m-d'));

        return Inertia::render('TrackingOutput/Index', [
            'outputs' => $allOutputs,
            'streakData' => $streakData,
            'stats' => $stats,
            'heatmap' => $heatmap,
            'goals' => [],
            'restDays' => $restDays,
            'currentDate' => $date,
            'currentView' => $view,
            'categories' => DailyOutput::CATEGORIES,
            'durationPresets' => DailyOutput::DURATION_PRESETS,
            'isPublic' => true,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(DailyOutput::CATEGORIES)),
            'output_date' => 'required|date|after_or_equal:' . DailyOutput::TRACKING_START . '|before_or_equal:' . DailyOutput::TRACKING_END,
            'goal_id' => 'nullable|exists:goals,id',
            'duration' => 'required|integer|min:1|max:1440',
            'note' => 'nullable|string|max:1000',
            'output_link' => 'nullable|string|max:500',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|max:5120',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:planned,done,skipped',
        ]);

        if (empty($validated['output_link'])) $validated['output_link'] = null;
        if (empty($validated['note'])) $validated['note'] = null;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('daily-outputs', 'public');
            }
        }
        $validated['images'] = $imagePaths ?: null;

        $validated['user_id'] = $request->user()->id;
        $validated['sort_order'] = DailyOutput::where('user_id', $request->user()->id)
            ->where('output_date', $validated['output_date'])
            ->count();

        if (isset($validated['goal_id'])) {
            $goal = $request->user()->goals()->find($validated['goal_id']);
            if (!$goal) $validated['goal_id'] = null;
        }

        DailyOutput::create($validated);

        return back()->with('success', 'Output added!');
    }

    public function update(Request $request, DailyOutput $dailyOutput)
    {
        if ($dailyOutput->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(DailyOutput::CATEGORIES)),
            'output_date' => 'required|date|after_or_equal:' . DailyOutput::TRACKING_START . '|before_or_equal:' . DailyOutput::TRACKING_END,
            'goal_id' => 'nullable|exists:goals,id',
            'duration' => 'required|integer|min:1|max:1440',
            'note' => 'nullable|string|max:1000',
            'output_link' => 'nullable|string|max:500',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|max:5120',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'string|max:500',
            'remove_image_path' => 'nullable|boolean',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:planned,done,skipped',
        ]);

        if (empty($validated['output_link'])) $validated['output_link'] = null;
        if (empty($validated['note'])) $validated['note'] = null;

        // Handle image_path (legacy single image)
        if ($request->boolean('remove_image_path') && $dailyOutput->image_path) {
            Storage::disk('public')->delete($dailyOutput->image_path);
            $validated['image_path'] = null;
        }

        // Handle removing specific images from JSON column
        $currentImages = $dailyOutput->images ?? [];
        $removedImages = $validated['removed_images'] ?? [];
        foreach ($removedImages as $path) {
            Storage::disk('public')->delete($path);
            $currentImages = array_values(array_filter($currentImages, fn($p) => $p !== $path));
        }

        // Add new uploaded images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $currentImages[] = $file->store('daily-outputs', 'public');
            }
        }
        $validated['images'] = $currentImages ?: null;
        unset($validated['removed_images'], $validated['remove_image_path']);

        $dailyOutput->update($validated);

        return back()->with('success', 'Output updated!');
    }

    public function destroy(Request $request, DailyOutput $dailyOutput)
    {
        if ($dailyOutput->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($dailyOutput->image_path) {
            Storage::disk('public')->delete($dailyOutput->image_path);
        }
        foreach ($dailyOutput->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $dailyOutput->delete();

        return back()->with('success', 'Output deleted!');
    }

    public function toggleStatus(Request $request, DailyOutput $dailyOutput)
    {
        if ($dailyOutput->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:planned,done,skipped',
        ]);

        $dailyOutput->update(['status' => $validated['status']]);

        return back();
    }

    public function markRestDay(Request $request)
    {
        $validated = $request->validate([
            'rest_date' => 'required|date|after_or_equal:' . DailyOutput::TRACKING_START . '|before_or_equal:' . DailyOutput::TRACKING_END,
        ]);

        $user = $request->user();
        $date = $validated['rest_date'];

        // Toggle: if already rest day, remove it
        $existing = OutputRestDay::where('user_id', $user->id)
            ->where('rest_date', $date)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Rest day removed.');
        }

        // Check if earned
        $calculator = new StreakCalculator($user->id);
        $streakData = $calculator->calculate();
        $isEarned = $streakData['rest_days_available'] > 0;

        OutputRestDay::create([
            'user_id' => $user->id,
            'rest_date' => $date,
            'is_earned' => $isEarned,
        ]);

        if ($isEarned) {
            return back()->with('success', 'Rest day marked! (Earned)');
        }

        return back()->with('error', 'Rest day marked but NOT earned - this will reset your streak!');
    }

    public function analytics(Request $request)
    {
        $user = $request->user();
        $startDate = DailyOutput::TRACKING_START;
        $endDate = DailyOutput::TRACKING_END;
        $categories = DailyOutput::CATEGORIES;

        // All done outputs
        $outputs = DailyOutput::where('user_id', $user->id)
            ->where('status', 'done')
            ->whereBetween('output_date', [$startDate, $endDate])
            ->selectRaw('category, duration, rating, output_date')
            ->get();

        $totalTime = $outputs->sum('duration');

        // Per-category stats
        $categoryStats = [];
        foreach ($categories as $key => $cat) {
            $catOutputs = $outputs->where('category', $key);
            $catTime = $catOutputs->sum('duration');
            $catCount = $catOutputs->count();
            $avgRating = $catCount > 0 ? round($catOutputs->avg('rating'), 1) : null;
            $flywheel = $cat['impact'] * $cat['compound'];
            $timeRatio = $totalTime > 0 ? round($catTime / $totalTime * 100, 1) : 0;
            $weightedScore = round($catTime * $flywheel / 100);

            $categoryStats[$key] = [
                'key' => $key,
                'icon' => $cat['icon'],
                'label' => $cat['label'],
                'impact' => $cat['impact'],
                'compound' => $cat['compound'],
                'flywheel' => $flywheel,
                'total_time' => $catTime,
                'total_count' => $catCount,
                'avg_rating' => $avgRating,
                'time_ratio' => $timeRatio,
                'weighted_score' => $weightedScore,
            ];
        }

        // Sort by flywheel desc for ranking
        $ranked = collect($categoryStats)->sortByDesc('flywheel')->values();

        // Total weighted score
        $totalWeightedScore = collect($categoryStats)->sum('weighted_score');

        // Weekly flywheel trend (last 8 weeks)
        $weeklyTrend = [];
        $trackingStart = Carbon::parse($startDate);
        $now = Carbon::now('Asia/Tokyo');
        // Build week buckets from tracking start
        $weekStart = $trackingStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekCount = 0;
        while ($weekStart->lte($now) && $weekCount < 12) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
            $weekStr = $weekStart->format('M d');
            $weekOutputs = $outputs->filter(function ($o) use ($weekStart, $weekEnd) {
                $d = Carbon::parse($o->output_date);
                return $d->between($weekStart, $weekEnd);
            });
            $weekWeighted = 0;
            foreach ($weekOutputs as $o) {
                $flywheel = $categories[$o->category]['impact'] * $categories[$o->category]['compound'];
                $weekWeighted += round($o->duration * $flywheel / 100);
            }
            $weeklyTrend[] = [
                'week' => $weekStr,
                'weighted_score' => $weekWeighted,
                'total_time' => $weekOutputs->sum('duration'),
            ];
            $weekStart->addWeek();
            $weekCount++;
        }

        // Recommendations
        $recommendations = [];
        foreach ($categoryStats as $key => $stat) {
            $flywheel = $stat['flywheel'];
            $timeRatio = $stat['time_ratio'];
            $catTime = $stat['total_time'];

            if ($catTime === 0 && $flywheel >= 56) {
                $recommendations[] = [
                    'category' => $key,
                    'icon' => $stat['icon'],
                    'label' => $stat['label'],
                    'type' => 'untapped',
                    'priority' => 'high',
                    'message' => "Not explored yet! Flywheel {$flywheel}/100 — high accumulation potential.",
                    'action' => 'Start now, even 30 mins/week makes a difference.',
                ];
            } elseif ($flywheel >= 63 && $timeRatio < 15 && $catTime > 0) {
                $recommendations[] = [
                    'category' => $key,
                    'icon' => $stat['icon'],
                    'label' => $stat['label'],
                    'type' => 'underinvested',
                    'priority' => 'high',
                    'message' => "Flywheel {$flywheel}/100 but only {$timeRatio}% of your time. Missing out on compound effect.",
                    'action' => 'Increase to at least 20% of total time.',
                ];
            } elseif ($flywheel >= 49 && $timeRatio >= 15 && $timeRatio <= 35) {
                $recommendations[] = [
                    'category' => $key,
                    'icon' => $stat['icon'],
                    'label' => $stat['label'],
                    'type' => 'balanced',
                    'priority' => 'good',
                    'message' => "Flywheel {$flywheel}/100 with {$timeRatio}% time. Good balance!",
                    'action' => 'Maintain and improve quality (rating).',
                ];
            } elseif ($flywheel >= 49 && $timeRatio > 35) {
                $recommendations[] = [
                    'category' => $key,
                    'icon' => $stat['icon'],
                    'label' => $stat['label'],
                    'type' => 'champion',
                    'priority' => 'great',
                    'message' => "🏆 Flywheel {$flywheel}/100 with {$timeRatio}% time. Your strongest activity!",
                    'action' => 'Keep going! This is your strongest flywheel.',
                ];
            } elseif ($flywheel < 36 && $timeRatio > 20) {
                $recommendations[] = [
                    'category' => $key,
                    'icon' => $stat['icon'],
                    'label' => $stat['label'],
                    'type' => 'overinvested',
                    'priority' => 'warning',
                    'message' => "Flywheel only {$flywheel}/100 but takes {$timeRatio}% of your time.",
                    'action' => 'Consider reducing below 15% to reinvest in higher-flywheel activities.',
                ];
            }
        }

        // Sort recommendations by priority
        $priorityOrder = ['warning' => 0, 'high' => 1, 'good' => 2, 'great' => 3];
        usort($recommendations, fn($a, $b) => ($priorityOrder[$a['priority']] ?? 9) <=> ($priorityOrder[$b['priority']] ?? 9));

        return Inertia::render('TrackingOutput/Analytics', [
            'categoryStats' => array_values($categoryStats),
            'ranked' => $ranked,
            'totalWeightedScore' => $totalWeightedScore,
            'totalTime' => $totalTime,
            'weeklyTrend' => $weeklyTrend,
            'recommendations' => $recommendations,
            'categories' => $categories,
        ]);
    }

    private function getStats(int $userId): array
    {
        $startDate = DailyOutput::TRACKING_START;
        $endDate = DailyOutput::TRACKING_END;

        $totalOutputs = DailyOutput::where('user_id', $userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$startDate, $endDate])
            ->count();

        $totalDuration = DailyOutput::where('user_id', $userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$startDate, $endDate])
            ->sum('duration');

        $activeDays = DailyOutput::where('user_id', $userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$startDate, $endDate])
            ->distinct('output_date')
            ->count('output_date');

        // Rate counts up to yesterday JST (today is still in progress)
        $yesterday = Carbon::now('Asia/Tokyo')->startOfDay()->subDay();
        $yesterdayStr = $yesterday->lte(Carbon::parse($endDate)) ? $yesterday->format('Y-m-d') : $endDate;
        $daysSinceStart = max(1, (int) Carbon::parse($startDate)->diffInDays($yesterday) + 1);

        // Recount activeDays up to yesterday only (consistent with daysSinceStart)
        $activeDaysUpToYesterday = DailyOutput::where('user_id', $userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$startDate, $yesterdayStr])
            ->distinct('output_date')
            ->count('output_date');

        $categoryDist = DailyOutput::where('user_id', $userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$startDate, $endDate])
            ->selectRaw('category, COUNT(*) as count, SUM(duration) as total_duration')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        return [
            'total_outputs' => $totalOutputs,
            'total_duration' => (int) $totalDuration,
            'active_days' => $activeDays,
            'completion_rate' => round(($activeDaysUpToYesterday / $daysSinceStart) * 100),
            'avg_duration_per_day' => $activeDays > 0 ? round($totalDuration / $activeDays) : 0,
            'category_distribution' => $categoryDist,
        ];
    }
}
