<x-mail::message>
# 📈 Monthly Review - {{ $data['month_name'] }}

Hello {{ $user->name }}!

Here's your monthly Vision Board summary for **{{ $data['month_name'] }}**.

---

## 📊 Monthly Statistics

<x-mail::table>
| Metric | Value |
|:-------|------:|
| Total Goals | {{ $data['total_goals'] }} |
| Completed Goals | {{ $data['completed_goals'] }} |
| Completion Rate | {{ $data['completion_rate'] }}% |
| Current Progress | {{ $data['current_progress'] }}% |
| Progress Change | {{ $data['progress_change'] >= 0 ? '+' : '' }}{{ $data['progress_change'] }}% |
</x-mail::table>

---

@if($data['completed_this_month']->count() > 0)
## 🎉 Goals Completed This Month

Congratulations on completing {{ $data['completed_this_month']->count() }} goal(s)!

@foreach($data['completed_this_month'] as $goal)
- {{ $goal->category->icon ?? '🎯' }} **{{ $goal->title }}**
@endforeach

@if($data['completed_last_month_count'] > 0)
*Compared to {{ $data['completed_last_month_count'] }} goal(s) last month.*
@endif

@else
## 📋 No Goals Completed Yet

You haven't completed any goals this month. Keep pushing - you can do it!
@endif

---

@if($data['best_category'])
## 🏆 Best Performing Category

**{{ $data['best_category']['category']->icon }} {{ $data['best_category']['category']->name }}**

- Average Progress: **{{ $data['best_category']['avg_progress'] }}%**
- Goals in Category: {{ $data['best_category']['count'] }}

Great work on this category! Keep the momentum going.
@endif

---

@if($data['needs_attention']->count() > 0)
## ⚠️ Goals Needing Attention

These goals have low progress and deadlines approaching:

@foreach($data['needs_attention']->take(5) as $goal)
- {{ $goal->category->icon ?? '🎯' }} **{{ $goal->title }}**: {{ $goal->progress }}% (Due: {{ $goal->target_date->format('M j') }})
@endforeach

Consider focusing on these goals to meet your deadlines.
@endif

---

## 📈 Progress Summary

@if($data['progress_change'] > 0)
🎉 **Great job!** Your overall progress increased by {{ $data['progress_change'] }}% this month.
@elseif($data['progress_change'] < 0)
📉 Your progress decreased by {{ abs($data['progress_change']) }}% this month. Let's pick up the pace next month!
@else
📊 Your progress remained steady this month. Keep pushing forward!
@endif

---

<x-mail::button :url="$goalsUrl">
View Your Vision Board
</x-mail::button>

<x-mail::button :url="$analyticsUrl" color="success">
View Detailed Analytics
</x-mail::button>

Here's to an even better month ahead! 🚀

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
