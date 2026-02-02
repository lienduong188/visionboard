<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'slogan',
        'cover_image',
        'target_value',
        'current_value',
        'unit',
        'progress',
        'start_date',
        'target_date',
        'completed_at',
        'priority',
        'status',
        'is_pinned',
        'is_core_goal',
        'sort_order',
        'orbit_scale',
    ];

    protected $casts = [
        'target_value' => 'integer',
        'current_value' => 'integer',
        'progress' => 'integer',
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_core_goal' => 'boolean',
        'orbit_scale' => 'integer',
    ];

    /**
     * Get the user that owns the goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the goal.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all milestones for this goal.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    /**
     * Get all images for this goal.
     */
    public function images(): HasMany
    {
        return $this->hasMany(GoalImage::class)->orderBy('sort_order');
    }

    /**
     * Get all reminders for this goal.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get all progress logs for this goal.
     */
    public function progressLogs(): HasMany
    {
        return $this->hasMany(ProgressLog::class)->orderByDesc('logged_at');
    }

    /**
     * Calculate progress based on current_value and target_value.
     */
    public function calculateProgress(): int
    {
        if (!$this->target_value || $this->target_value == 0) {
            return $this->progress;
        }
        return min(100, (int) round(($this->current_value / $this->target_value) * 100));
    }

    /**
     * Update progress and log the change.
     */
    public function updateProgress(int $newValue, ?string $note = null): void
    {
        $previousValue = $this->current_value;
        $previousProgress = $this->progress;

        $this->current_value = $newValue;
        $this->progress = $this->calculateProgress();

        if ($this->progress >= 100 && $this->status !== 'completed') {
            $this->status = 'completed';
            $this->completed_at = now();
        }

        $this->save();

        $this->progressLogs()->create([
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'previous_progress' => $previousProgress,
            'new_progress' => $this->progress,
            'note' => $note,
        ]);
    }

    /**
     * Scope for pinned goals.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for core goals (3 trục trung tâm).
     */
    public function scopeCoreGoals($query)
    {
        return $query->where('is_core_goal', true);
    }

    /**
     * Scope for regular goals (non-core).
     */
    public function scopeRegularGoals($query)
    {
        return $query->where('is_core_goal', false);
    }

    /**
     * Scope for active goals.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['not_started', 'in_progress']);
    }

    /**
     * Scope for goals by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
