<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'weekly_enabled',
        'monthly_enabled',
        'weekly_day',
        'monthly_day',
        'send_time',
        'last_weekly_sent_at',
        'last_monthly_sent_at',
    ];

    protected $casts = [
        'weekly_enabled' => 'boolean',
        'monthly_enabled' => 'boolean',
        'weekly_day' => 'integer',
        'monthly_day' => 'integer',
        'last_weekly_sent_at' => 'datetime',
        'last_monthly_sent_at' => 'datetime',
    ];

    /**
     * Get the user that owns this setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if weekly review should be sent today.
     */
    public function shouldSendWeekly(): bool
    {
        if (!$this->weekly_enabled) {
            return false;
        }

        $now = Carbon::now();
        $currentDayOfWeek = $now->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)

        // Check if today is the scheduled day
        if ($currentDayOfWeek !== $this->weekly_day) {
            return false;
        }

        // Check send time (within 5 minutes window)
        $sendTime = Carbon::parse($this->send_time);
        $sendTimeToday = $now->copy()->setTime($sendTime->hour, $sendTime->minute);

        if ($now->lt($sendTimeToday) || $now->gt($sendTimeToday->copy()->addMinutes(5))) {
            return false;
        }

        // Check if already sent this week
        if ($this->last_weekly_sent_at) {
            $lastSent = Carbon::parse($this->last_weekly_sent_at);
            if ($lastSent->isSameWeek($now)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if monthly review should be sent today.
     */
    public function shouldSendMonthly(): bool
    {
        if (!$this->monthly_enabled) {
            return false;
        }

        $now = Carbon::now();
        $currentDayOfMonth = $now->day;

        // Check if today is the scheduled day (handle month end edge cases)
        $scheduledDay = min($this->monthly_day, $now->daysInMonth);
        if ($currentDayOfMonth !== $scheduledDay) {
            return false;
        }

        // Check send time (within 5 minutes window)
        $sendTime = Carbon::parse($this->send_time);
        $sendTimeToday = $now->copy()->setTime($sendTime->hour, $sendTime->minute);

        if ($now->lt($sendTimeToday) || $now->gt($sendTimeToday->copy()->addMinutes(5))) {
            return false;
        }

        // Check if already sent this month
        if ($this->last_monthly_sent_at) {
            $lastSent = Carbon::parse($this->last_monthly_sent_at);
            if ($lastSent->isSameMonth($now)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get day name from number.
     */
    public static function getDayName(int $day): string
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $days[$day] ?? 'Monday';
    }
}
