<x-mail::message>
# {{ $reminder->type === 'deadline' ? '⏰ Deadline Reminder' : ($reminder->type === 'progress' ? '📊 Progress Check' : '🔔 Goal Reminder') }}

Hello!

This is a reminder about your goal: **{{ $goal->title }}**

@if($goal->category)
**Category:** {{ $goal->category->icon }} {{ $goal->category->name }}
@endif

**Current Progress:** {{ $goal->progress }}%

@if($goal->target_value && $goal->unit)
**Progress:** {{ formatNumber($goal->current_value ?? 0) }} / {{ formatNumber($goal->target_value) }} {{ $goal->unit }}
@endif

@if($goal->target_date)
**Target Date:** {{ $goal->target_date->format('Y-m-d') }}
@endif

@if($reminder->message)
---
**Note:** {{ $reminder->message }}
@endif

<x-mail::button :url="$goalUrl">
View Goal
</x-mail::button>

Keep pushing towards your goals! You've got this! 💪

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
