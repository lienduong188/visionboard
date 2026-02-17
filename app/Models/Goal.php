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
        'start_value',
        'progress',
        'progress_mode', // 'milestone' or 'value'
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
        'target_value' => 'float',
        'current_value' => 'float',
        'start_value' => 'float',
        'progress' => 'integer',
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_core_goal' => 'boolean',
        'orbit_scale' => 'integer',
    ];

    protected $appends = ['cover_image_url'];

    /**
     * Get the full URL for cover image (fix for subfolder deployment).
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image) {
            return null;
        }

        // If already full URL, return as is
        if (str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }

        // Fix relative URL for production subfolder deployment
        return config('app.url') . $this->cover_image;
    }

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
     * Get all checklist items for this goal.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(GoalChecklist::class)->orderBy('sort_order');
    }

    /**
     * Get all references for this goal.
     */
    public function references(): HasMany
    {
        return $this->hasMany(GoalReference::class)->orderBy('sort_order');
    }

    public function dailyOutputs(): HasMany
    {
        return $this->hasMany(DailyOutput::class);
    }

    /**
     * Check if this is a decrease goal (e.g., reduce body fat).
     */
    public function isDecreaseGoal(): bool
    {
        return $this->start_value !== null && $this->start_value > $this->target_value;
    }

    /**
     * Calculate progress based on current_value, target_value, and start_value.
     * Supports both increase goals (run 100km) and decrease goals (reduce fat from 27% to 20%).
     */
    public function calculateProgress(): int
    {
        if (!$this->target_value || $this->target_value == 0) {
            return 0;
        }

        // Decrease goal: progress = (start - current) / (start - target) * 100
        if ($this->isDecreaseGoal()) {
            $totalDecrease = $this->start_value - $this->target_value;
            if ($totalDecrease == 0) {
                return 100;
            }
            $currentDecrease = $this->start_value - $this->current_value;
            return max(0, min(100, (int) round(($currentDecrease / $totalDecrease) * 100)));
        }

        // Increase goal: progress = current / target * 100
        return min(100, (int) round(($this->current_value / $this->target_value) * 100));
    }

    /**
     * Calculate progress for a given value (without saving).
     */
    public function calculateProgressForValue(float $value): int
    {
        if (!$this->target_value || $this->target_value == 0) {
            return 0;
        }

        // Decrease goal: progress = (start - value) / (start - target) * 100
        if ($this->isDecreaseGoal()) {
            $totalDecrease = $this->start_value - $this->target_value;
            if ($totalDecrease == 0) {
                return 100;
            }
            $currentDecrease = $this->start_value - $value;
            return max(0, min(100, (int) round(($currentDecrease / $totalDecrease) * 100)));
        }

        // Increase goal: progress = value / target * 100
        return min(100, (int) round(($value / $this->target_value) * 100));
    }

    /**
     * Update progress and log the change.
     */
    public function updateProgress(float $newValue, ?string $note = null): void
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
