<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GoalController extends Controller
{
    /**
     * Display the vision board (main page).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $goals = Goal::with(['category', 'milestones', 'progressLogs'])
            ->where('user_id', $user->id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $categories = Category::ordered()->get();

        // Separate core goals and regular goals
        $coreGoals = $goals->where('is_core_goal', true)->values();
        $regularGoals = $goals->where('is_core_goal', false)->values();

        // Calculate stats
        $stats = [
            'total' => $goals->count(),
            'completed' => $goals->where('status', 'completed')->count(),
            'in_progress' => $goals->where('status', 'in_progress')->count(),
            'not_started' => $goals->where('status', 'not_started')->count(),
            'overall_progress' => $goals->count() > 0
                ? round($goals->avg('progress'))
                : 0,
        ];

        // Group goals by category for plan view
        $goalsByCategory = $goals->groupBy('category_id');

        // Handle view parameter - redirect dashboard to analytics
        $view = $request->get('view', 'visionboard');
        if ($view === 'dashboard') {
            return redirect()->route('analytics.index');
        }

        return Inertia::render('Goals/Index', [
            'goals' => $goals,
            'coreGoals' => $coreGoals,
            'regularGoals' => $regularGoals,
            'goalsByCategory' => $goalsByCategory,
            'categories' => $categories,
            'stats' => $stats,
            'view' => $view,
        ]);
    }

    /**
     * Show the form for creating a new goal.
     */
    public function create(Request $request)
    {
        $categories = Category::ordered()->get();
        $coreGoalsCount = Goal::where('user_id', $request->user()->id)
            ->where('is_core_goal', true)
            ->count();

        return Inertia::render('Goals/Create', [
            'categories' => $categories,
            'coreGoalsCount' => $coreGoalsCount,
        ]);
    }

    /**
     * Store a newly created goal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'target_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'is_core_goal' => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'not_started';
        $validated['progress'] = 0;

        // Check core goals limit (max 3)
        if ($request->boolean('is_core_goal')) {
            $coreGoalsCount = Goal::where('user_id', $request->user()->id)
                ->where('is_core_goal', true)
                ->count();

            if ($coreGoalsCount >= 3) {
                return back()->withErrors([
                    'is_core_goal' => 'Bạn chỉ có thể có tối đa 3 Core Goals (trục trung tâm).',
                ])->withInput();
            }
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('goals', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        } else {
            unset($validated['cover_image']);
        }

        Goal::create($validated);

        return redirect()->route('goals.index')
            ->with('success', 'Goal created successfully!');
    }

    /**
     * Display the specified goal.
     */
    public function show(Goal $goal)
    {
        $this->authorize('view', $goal);

        $goal->load(['category', 'milestones', 'images', 'progressLogs', 'reminders']);

        return Inertia::render('Goals/Show', [
            'goal' => $goal,
        ]);
    }

    /**
     * Show the form for editing the specified goal.
     */
    public function edit(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $categories = Category::ordered()->get();
        $coreGoalsCount = Goal::where('user_id', $request->user()->id)
            ->where('is_core_goal', true)
            ->where('id', '!=', $goal->id)
            ->count();

        return Inertia::render('Goals/Edit', [
            'goal' => $goal,
            'categories' => $categories,
            'coreGoalsCount' => $coreGoalsCount,
        ]);
    }

    /**
     * Update the specified goal.
     */
    public function update(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'remove_cover_image' => 'nullable|boolean',
            'target_value' => 'nullable|numeric',
            'current_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:not_started,in_progress,completed,paused,cancelled',
            'is_pinned' => 'boolean',
            'is_core_goal' => 'boolean',
        ]);

        // Check core goals limit (max 3)
        if ($request->boolean('is_core_goal') && !$goal->is_core_goal) {
            $coreGoalsCount = Goal::where('user_id', $request->user()->id)
                ->where('is_core_goal', true)
                ->where('id', '!=', $goal->id)
                ->count();

            if ($coreGoalsCount >= 3) {
                return back()->withErrors([
                    'is_core_goal' => 'Bạn chỉ có thể có tối đa 3 Core Goals (trục trung tâm).',
                ])->withInput();
            }
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old image if exists
            if ($goal->cover_image && str_starts_with($goal->cover_image, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $goal->cover_image);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('cover_image')->store('goals', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        } elseif ($request->boolean('remove_cover_image')) {
            // Delete old image
            if ($goal->cover_image && str_starts_with($goal->cover_image, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $goal->cover_image);
                Storage::disk('public')->delete($oldPath);
            }
            $validated['cover_image'] = null;
        } else {
            unset($validated['cover_image']);
        }
        unset($validated['remove_cover_image']);

        // Calculate progress if target_value exists
        if (isset($validated['target_value']) && $validated['target_value'] > 0) {
            $validated['progress'] = min(100, round(($validated['current_value'] ?? 0) / $validated['target_value'] * 100));
        }

        $goal->update($validated);

        return redirect()->route('goals.index')
            ->with('success', 'Goal updated successfully!');
    }

    /**
     * Update goal progress quickly.
     */
    public function updateProgress(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'current_value' => 'required|numeric',
            'note' => 'nullable|string',
        ]);

        $goal->updateProgress($validated['current_value'], $validated['note'] ?? null);

        return back()->with('success', 'Progress updated!');
    }

    /**
     * Toggle pin status.
     */
    public function togglePin(Goal $goal)
    {
        $this->authorize('update', $goal);

        $goal->update(['is_pinned' => !$goal->is_pinned]);

        return back();
    }

    /**
     * Update orbit scale for a goal.
     */
    public function updateOrbitScale(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'orbit_scale' => 'required|integer|min:1|max:5',
        ]);

        $goal->update($validated);

        return back();
    }

    /**
     * Reorder goals via drag & drop.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'goals' => 'required|array',
            'goals.*.id' => 'required|integer|exists:goals,id',
            'goals.*.sort_order' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        foreach ($validated['goals'] as $goalData) {
            Goal::where('id', $goalData['id'])
                ->where('user_id', $user->id)
                ->update(['sort_order' => $goalData['sort_order']]);
        }

        return back();
    }

    /**
     * Remove the specified goal.
     */
    public function destroy(Goal $goal)
    {
        $this->authorize('delete', $goal);

        // Delete cover image if exists
        if ($goal->cover_image && str_starts_with($goal->cover_image, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $goal->cover_image);
            Storage::disk('public')->delete($oldPath);
        }

        $goal->delete();

        return redirect()->route('goals.index')
            ->with('success', 'Goal deleted successfully!');
    }
}
