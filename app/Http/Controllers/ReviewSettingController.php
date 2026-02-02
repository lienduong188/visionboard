<?php

namespace App\Http\Controllers;

use App\Models\ReviewSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewSettingController extends Controller
{
    /**
     * Display the review settings form.
     */
    public function show(Request $request)
    {
        $setting = $request->user()->reviewSetting;

        // Create default settings if none exist
        if (!$setting) {
            $setting = new ReviewSetting([
                'weekly_enabled' => false,
                'monthly_enabled' => false,
                'weekly_day' => 1,
                'monthly_day' => 1,
                'send_time' => '09:00',
            ]);
        }

        return Inertia::render('Settings/Reviews', [
            'setting' => $setting,
            'days' => [
                ['value' => 1, 'label' => 'Monday'],
                ['value' => 2, 'label' => 'Tuesday'],
                ['value' => 3, 'label' => 'Wednesday'],
                ['value' => 4, 'label' => 'Thursday'],
                ['value' => 5, 'label' => 'Friday'],
                ['value' => 6, 'label' => 'Saturday'],
                ['value' => 7, 'label' => 'Sunday'],
            ],
        ]);
    }

    /**
     * Update the review settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'weekly_enabled' => 'boolean',
            'monthly_enabled' => 'boolean',
            'weekly_day' => 'integer|min:1|max:7',
            'monthly_day' => 'integer|min:1|max:28',
            'send_time' => 'required|date_format:H:i',
        ]);

        $request->user()->reviewSetting()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return back()->with('success', 'Review settings updated!');
    }
}
