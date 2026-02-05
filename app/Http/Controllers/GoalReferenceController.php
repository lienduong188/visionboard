<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\GoalReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoalReferenceController extends Controller
{
    /**
     * Store a newly created reference.
     */
    public function store(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'type' => 'required|in:link,file,text',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // max 10MB
        ]);

        $data = [
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'sort_order' => $goal->references()->count() + 1,
        ];

        // Handle file upload
        if ($validated['type'] === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('references/' . $goal->id, 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }

        $goal->references()->create($data);

        return back()->with('success', 'Reference added!');
    }

    /**
     * Update the specified reference.
     */
    public function update(Request $request, Goal $goal, GoalReference $reference)
    {
        $this->authorize('update', $goal);

        if ($reference->goal_id !== $goal->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'] ?? $reference->content,
        ];

        // Handle file replacement
        if ($reference->type === 'file' && $request->hasFile('file')) {
            // Delete old file
            if ($reference->file_path) {
                Storage::disk('public')->delete($reference->file_path);
            }

            $file = $request->file('file');
            $path = $file->store('references/' . $goal->id, 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }

        $reference->update($data);

        return back()->with('success', 'Reference updated!');
    }

    /**
     * Reorder references.
     */
    public function reorder(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:goal_references,id',
        ]);

        foreach ($validated['order'] as $index => $referenceId) {
            GoalReference::where('id', $referenceId)
                ->where('goal_id', $goal->id)
                ->update(['sort_order' => $index + 1]);
        }

        return back();
    }

    /**
     * Remove the specified reference.
     */
    public function destroy(Goal $goal, GoalReference $reference)
    {
        $this->authorize('update', $goal);

        if ($reference->goal_id !== $goal->id) {
            abort(403);
        }

        // Delete file if exists
        if ($reference->type === 'file' && $reference->file_path) {
            Storage::disk('public')->delete($reference->file_path);
        }

        $reference->delete();

        return back()->with('success', 'Reference deleted!');
    }
}
