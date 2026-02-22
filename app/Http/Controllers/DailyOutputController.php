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
            'output_link' => 'nullable|url|max:500',
            'image' => 'nullable|image|max:5120',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:planned,done,skipped',
        ]);

        if (empty($validated['output_link'])) $validated['output_link'] = null;
        if (empty($validated['note'])) $validated['note'] = null;

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('daily-outputs', 'public');
        }
        unset($validated['image']);

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
            'output_link' => 'nullable|url|max:500',
            'image' => 'nullable|image|max:5120',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => 'required|in:planned,done,skipped',
        ]);

        if (empty($validated['output_link'])) $validated['output_link'] = null;
        if (empty($validated['note'])) $validated['note'] = null;

        if ($request->hasFile('image')) {
            if ($dailyOutput->image_path) {
                Storage::disk('public')->delete($dailyOutput->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('daily-outputs', 'public');
        } elseif ($request->boolean('remove_image') && $dailyOutput->image_path) {
            Storage::disk('public')->delete($dailyOutput->image_path);
            $validated['image_path'] = null;
        }
        unset($validated['image']);

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

    private function getStats(int $userId): array
    {
        $startDate = Carbon::parse(DailyOutput::TRACKING_START);
        $endDate = Carbon::parse(DailyOutput::TRACKING_END);

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
        $yesterday = Carbon::now('Asia/Tokyo')->startOfDay()->subDay()->min($endDate);
        $daysSinceStart = max(1, (int) $startDate->diffInDays($yesterday) + 1);

        // Recount activeDays up to yesterday only (consistent with daysSinceStart)
        $activeDaysUpToYesterday = DailyOutput::where('user_id', $userId)
            ->where('status', 'done')
            ->whereBetween('output_date', [$startDate, $yesterday])
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
