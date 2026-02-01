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
        'remind_time',
        'message',
        'is_active',
        'last_sent_at',
        'next_send_at',
    ];

    protected $casts = [
        'remind_time' => 'datetime:H:i',
        'is_active' => 'boolean',
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

        $next = match($this->frequency) {
            'daily' => $now->copy()->setTimeFrom($time)->addDay(),
            'weekly' => $now->copy()->setTimeFrom($time)->next(Carbon::MONDAY),
            'monthly' => $now->copy()->setTimeFrom($time)->addMonth()->startOfMonth(),
            'custom' => $this->calculateCustomNext($now, $time),
            default => $now->copy()->setTimeFrom($time)->addDay(),
        };

        $this->update(['next_send_at' => $next]);
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
