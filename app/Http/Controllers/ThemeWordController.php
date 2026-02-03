<?php

namespace App\Http\Controllers;

use App\Models\ThemeWord;
use Illuminate\Http\Request;

class ThemeWordController extends Controller
{
    /**
     * Store a new theme word.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $maxOrder = ThemeWord::where('user_id', $request->user()->id)->max('sort_order') ?? -1;

        ThemeWord::create([
            'user_id' => $request->user()->id,
            'word' => $validated['word'],
            'color' => $validated['color'] ?? '#6366F1',
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    /**
     * Update a theme word.
     */
    public function update(Request $request, ThemeWord $themeWord)
    {
        $this->authorize('update', $themeWord);

        $validated = $request->validate([
            'word' => 'required|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $themeWord->update($validated);

        return back();
    }

    /**
     * Delete a theme word.
     */
    public function destroy(ThemeWord $themeWord)
    {
        $this->authorize('delete', $themeWord);

        $themeWord->delete();

        return back();
    }

    /**
     * Reorder theme words.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'words' => 'required|array',
            'words.*.id' => 'required|integer|exists:theme_words,id',
            'words.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['words'] as $wordData) {
            ThemeWord::where('id', $wordData['id'])
                ->where('user_id', $request->user()->id)
                ->update(['sort_order' => $wordData['sort_order']]);
        }

        return back();
    }

    /**
     * Update theme words effect mode.
     */
    public function updateEffect(Request $request)
    {
        $validated = $request->validate([
            'effect' => 'required|in:orbit,waterfall',
        ]);

        $request->user()->update([
            'theme_words_effect' => $validated['effect'],
        ]);

        return back();
    }
}
