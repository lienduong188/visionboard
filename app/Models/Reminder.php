<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'type',
        'frequency',
        'custom_days',
        'weekly_days',
        'monthly_day',
        'start_date',
        'end_date',
        'remind_time',
        'message',
        'is_active',
        'last_sent_at',
        'next_send_at',
    ];

    protected $casts = [
        'remind_time' => 'datetime:H:i',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
    ];

    /**
     * Get the goal that owns the reminder.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Calculate and set the next send time.
     */
    public function calculateNextSendAt(): void
    {
        $now = Carbon::now();
        $time = Carbon::parse($this->remind_time);

        // Check if reminder is within date range
        if ($this->start_date && $now->lt($this->start_date->startOfDay())) {
            // Before start date - set next to start date
            $next = $this->start_date->copy()->setTimeFrom($time);
            $this->update(['next_send_at' => $next]);
            return;
        }

        if ($this->end_date && $now->gt($this->end_date->endOfDay())) {
            // After end date - disable reminder
            $this->update(['next_send_at' => null, 'is_active' => false]);
            return;
        }

        $next = match($this->frequency) {
            'daily' => $this->calculateDailyNext($now, $time),
            'weekly' => $this->calculateWeeklyNext($now, $time),
            'monthly' => $this->calculateMonthlyNext($now, $time),
            'custom' => $this->calculateCustomNext($now, $time),
            default => $now->copy()->setTimeFrom($time)->addDay(),
        };

        // If next is after end_date, disable reminder
        if ($this->end_date && $next->gt($this->end_date->endOfDay())) {
            $this->update(['next_send_at' => null, 'is_active' => false]);
            return;
        }

        $this->update(['next_send_at' => $next]);
    }

    /**
     * Calculate next daily send time.
     */
    protected function calculateDailyNext(Carbon $now, Carbon $time): Carbon
    {
        $next = $now->copy()->setTimeFrom($time);
        if ($next->lte($now)) {
            $next->addDay();
        }
        return $next;
    }

    /**
     * Calculate next weekly send time based on selected days.
     */
    protected function calculateWeeklyNext(Carbon $now, Carbon $time): Carbon
    {
        // If weekly_days is set (e.g., "1,3,5" for Mon, Wed, Fri)
        if ($this->weekly_days) {
            $days = array_map('intval', explode(',', $this->weekly_days));
            sort($days);
            $currentDayOfWeek = $now->dayOfWeekIso; // 1=Mon, 7=Sun

            // Find next day this week
            foreach ($days as $day) {
                if ($day > $currentDayOfWeek || ($day == $currentDayOfWeek && $now->copy()->setTimeFrom($time)->gt($now))) {
                    $next = $now->copy()->startOfWeek()->addDays($day - 1)->setTimeFrom($time);
                    if ($next->gt($now)) {
                        return $next;
                    }
                }
            }

            // Next week's first selected day
            return $now->copy()->addWeek()->startOfWeek()->addDays($days[0] - 1)->setTimeFrom($time);
        }

        // Default: next Monday
        $next = $now->copy()->setTimeFrom($time)->next(Carbon::MONDAY);
        return $next;
    }

    /**
     * Calculate next monthly send time based on selected day of month.
     */
    protected function calculateMonthlyNext(Carbon $now, Carbon $time): Carbon
    {
        $dayOfMonth = $this->monthly_day ?? 1;

        // Try this month
        $next = $now->copy()->setDay(min($dayOfMonth, $now->daysInMonth))->setTimeFrom($time);

        if ($next->lte($now)) {
            // Move to next month
            $next = $now->copy()->addMonth();
            $next->setDay(min($dayOfMonth, $next->daysInMonth))->setTimeFrom($time);
        }

        return $next;
    }

    /**
     * Calculate next send time for custom frequency.
     */
    protected function calculateCustomNext(Carbon $now, Carbon $time): Carbon
    {
        if (!$this->custom_days) {
            return $now->copy()->setTimeFrom($time)->addDay();
        }

        $days = array_map('intval', explode(',', $this->custom_days));
        $currentDayOfWeek = $now->dayOfWeekIso;

        foreach ($days as $day) {
            if ($day > $currentDayOfWeek) {
                return $now->copy()->setTimeFrom($time)->next($day);
            }
        }

        // Next week's first custom day
        return $now->copy()->setTimeFrom($time)->addWeek()->startOfWeek()->next($days[0]);
    }

    /**
     * Mark reminder as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['last_sent_at' => now()]);
        $this->calculateNextSendAt();
    }

    /**
     * Scope for active reminders.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for reminders due to be sent.
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
                    ->where('next_send_at', '<=', now());
    }
}
