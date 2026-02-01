<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Goal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalController extends Controller
{
    /**
     * Display the vision board (main page).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $goals = Goal::with(['category', 'milestones'])
            ->where('user_id', $user->id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('target_date', 'asc')
            ->get();

        $categories = Category::ordered()->get();

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

        // Group goals by category for board view
        $goalsByCategory = $goals->groupBy('category_id');

        return Inertia::render('Goals/Index', [
            'goals' => $goals,
            'goalsByCategory' => $goalsByCategory,
            'categories' => $categories,
            'stats' => $stats,
            'view' => $request->get('view', 'board'), // default to board view
        ]);
    }

    /**
     * Show the form for creating a new goal.
     */
    public function create()
    {
        $categories = Category::ordered()->get();

        return Inertia::render('Goals/Create', [
            'categories' => $categories,
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
            'cover_image' => 'nullable|string|max:500',
            'target_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'not_started';
        $validated['progress'] = 0;

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

        $goal->load(['category', 'milestones', 'images', 'progressLogs']);

        return Inertia::render('Goals/Show', [
            'goal' => $goal,
        ]);
    }

    /**
     * Show the form for editing the specified goal.
     */
    public function edit(Goal $goal)
    {
        $this->authorize('update', $goal);

        $categories = Category::ordered()->get();

        return Inertia::render('Goals/Edit', [
            'goal' => $goal,
            'categories' => $categories,
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
            'cover_image' => 'nullable|string|max:500',
            'target_value' => 'nullable|numeric',
            'current_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:not_started,in_progress,completed,paused,cancelled',
            'is_pinned' => 'boolean',
        ]);

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
     * Remove the specified goal.
     */
    public function destroy(Goal $goal)
    {
        $this->authorize('delete', $goal);

        $goal->delete();

        return redirect()->route('goals.index')
            ->with('success', 'Goal deleted successfully!');
    }
}
