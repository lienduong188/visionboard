<x-mail::message>
# 📊 Weekly Review

Hello {{ $user->name }}!

Here's your weekly Vision Board summary for **{{ $data['week_start'] }} - {{ $data['week_end'] }}**.

---

## 📈 Overview

- **Total Goals:** {{ $data['total_goals'] }}
- **Overall Progress:** {{ $data['overall_progress'] }}%

---

@if($data['completed_this_week']->count() > 0)
## 🎉 Goals Completed This Week

@foreach($data['completed_this_week'] as $goal)
- {{ $goal->category->icon ?? '🎯' }} **{{ $goal->title }}** - 100% Complete!
@endforeach

@endif

@if($data['goals_with_progress']->count() > 0)
## 💪 Goals with Progress This Week

@foreach($data['goals_with_progress'] as $goal)
- {{ $goal->category->icon ?? '🎯' }} **{{ $goal->title }}**: {{ $goal->progress }}%
@endforeach

@endif

@if($data['stalled_goals']->count() > 0)
## ⚠️ Goals Needing Attention

These goals haven't seen progress this week:

@foreach($data['stalled_goals']->take(5) as $goal)
- {{ $goal->category->icon ?? '🎯' }} **{{ $goal->title }}**: {{ $goal->progress }}%
@endforeach

@if($data['stalled_goals']->count() > 5)
...and {{ $data['stalled_goals']->count() - 5 }} more
@endif

@endif

@if($data['upcoming_deadlines']->count() > 0)
## 📅 Upcoming Deadlines (Next 7 Days)

@foreach($data['upcoming_deadlines'] as $goal)
- {{ $goal->category->icon ?? '🎯' }} **{{ $goal->title }}** - Due: {{ $goal->target_date->format('M j, Y') }}
@endforeach

@endif

---

<x-mail::button :url="$goalsUrl">
View Your Vision Board
</x-mail::button>

<x-mail::button :url="$analyticsUrl" color="success">
View Analytics
</x-mail::button>

Keep pushing towards your goals! You've got this! 💪

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
