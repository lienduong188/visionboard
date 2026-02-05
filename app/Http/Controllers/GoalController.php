<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Goal;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

        $goals = Goal::with(['category', 'milestones.todos', 'progressLogs', 'checklists', 'reminders'])
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

        // Theme words for VisionBoard
        $themeWords = $user->themeWords()->get();
        $themeWordsEffect = $user->theme_words_effect ?? 'orbit';

        return Inertia::render('Goals/Index', [
            'goals' => $goals,
            'coreGoals' => $coreGoals,
            'regularGoals' => $regularGoals,
            'goalsByCategory' => $goalsByCategory,
            'categories' => $categories,
            'stats' => $stats,
            'view' => $view,
            'themeWords' => $themeWords,
            'themeWordsEffect' => $themeWordsEffect,
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
            'slogan' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'target_value' => 'nullable|numeric',
            'start_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'is_core_goal' => 'boolean',
            'progress_mode' => 'nullable|in:value,milestone',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'not_started';
        $validated['progress'] = 0;
        $validated['progress_mode'] = $validated['progress_mode'] ?? 'value';

        // Default current_value: start_value if set, otherwise 0
        $validated['current_value'] = $validated['start_value'] ?? 0;

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

        $goal->load(['category', 'milestones.todos', 'images', 'progressLogs', 'reminders', 'checklists']);

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
            'slogan' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'remove_cover_image' => 'nullable|boolean',
            'target_value' => 'nullable|numeric',
            'current_value' => 'nullable|numeric',
            'start_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:not_started,in_progress,completed,paused,cancelled',
            'is_pinned' => 'boolean',
            'is_core_goal' => 'boolean',
            'progress_mode' => 'nullable|in:value,milestone',
        ]);

        // Default current_value: start_value if set, otherwise 0
        if (!isset($validated['current_value']) || $validated['current_value'] === null || $validated['current_value'] === '') {
            $validated['current_value'] = $validated['start_value'] ?? $goal->start_value ?? 0;
        }

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

        // First update the goal with new values
        $goal->update($validated);

        // Recalculate progress based on progress_mode
        if ($goal->progress_mode === 'milestone') {
            // Tính theo milestones (non-soft)
            $totalMilestones = $goal->milestones()->where('is_soft', false)->count();
            if ($totalMilestones > 0) {
                $completedMilestones = $goal->milestones()
                    ->where('is_soft', false)
                    ->where('is_completed', true)
                    ->count();
                $goal->progress = (int) round(($completedMilestones / $totalMilestones) * 100);
            } else {
                $goal->progress = 0;
            }
            $goal->save();
        } elseif ($goal->progress_mode === 'value' && $goal->target_value && $goal->target_value > 0) {
            // Tính theo current_value/target_value
            $goal->progress = $goal->calculateProgress();
            $goal->save();
        }

        return redirect()->route('goals.index', ['view' => 'plan'])
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

        $goal->updateProgress((float) $validated['current_value'], $validated['note'] ?? null);

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

        return redirect()->route('goals.index', ['view' => 'plan'])
            ->with('success', 'Goal deleted successfully!');
    }

    /**
     * Export goals to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();

        $goals = Goal::with(['category', 'milestones'])
            ->where('user_id', $user->id)
            ->orderBy('is_core_goal', 'desc')
            ->orderBy('sort_order', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="goals_' . date('Y-m-d') . '.csv"',
        ];

        $columns = [
            'Title',
            'Category',
            'Status',
            'Priority',
            'Progress (%)',
            'Target Value',
            'Current Value',
            'Unit',
            'Start Date',
            'Target Date',
            'Core Goal',
            'Slogan',
            'Description',
            'Milestones',
        ];

        $callback = function () use ($goals, $columns) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $columns);

            foreach ($goals as $goal) {
                $milestones = $goal->milestones->pluck('title')->implode('; ');

                fputcsv($file, [
                    $goal->title,
                    $goal->category?->name ?? '',
                    $goal->status,
                    $goal->priority,
                    $goal->progress,
                    $goal->target_value,
                    $goal->current_value,
                    $goal->unit,
                    $goal->start_date?->format('Y-m-d'),
                    $goal->target_date?->format('Y-m-d'),
                    $goal->is_core_goal ? 'Yes' : 'No',
                    $goal->slogan,
                    $goal->description,
                    $milestones,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export goals to PDF.
     */
    public function exportPdf(Request $request)
    {
        $user = $request->user();

        // Order: Core Goals -> Priority (high->medium->low) -> Status -> Target Date
        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
        $statusOrder = ['completed' => 1, 'in_progress' => 2, 'not_started' => 3, 'paused' => 4, 'cancelled' => 5];

        $goals = Goal::with(['category', 'milestones'])
            ->where('user_id', $user->id)
            ->get()
            ->sortBy([
                ['is_core_goal', 'desc'],
                fn($a, $b) => ($priorityOrder[$a->priority] ?? 99) <=> ($priorityOrder[$b->priority] ?? 99),
                fn($a, $b) => ($statusOrder[$a->status] ?? 99) <=> ($statusOrder[$b->status] ?? 99),
                ['target_date', 'asc'],
            ])
            ->values();

        $coreGoals = $goals->where('is_core_goal', true)->values();
        $regularGoals = $goals->where('is_core_goal', false)->values();

        $stats = [
            'total' => $goals->count(),
            'completed' => $goals->where('status', 'completed')->count(),
            'in_progress' => $goals->where('status', 'in_progress')->count(),
            'not_started' => $goals->where('status', 'not_started')->count(),
            'overall_progress' => $goals->count() > 0
                ? round($goals->avg('progress'))
                : 0,
        ];

        // Use mPDF for CJK (Japanese/Vietnamese) support
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'sans-serif',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $html = view('exports.goals-pdf', [
            'goals' => $goals,
            'coreGoals' => $coreGoals,
            'regularGoals' => $regularGoals,
            'stats' => $stats,
            'user' => $user,
            'exportDate' => now()->format('Y-m-d H:i'),
        ])->render();

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="goals_' . date('Y-m-d') . '.pdf"',
        ]);
    }
}
