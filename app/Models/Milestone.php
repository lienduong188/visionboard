<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Milestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'title',
        'description',
        'memo',
        'image_path',
        'target_value',
        'due_date',
        'completed_at',
        'is_completed',
        'sort_order',
        'is_soft',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
        'is_soft' => 'boolean',
    ];

    protected $appends = ['image_url'];

    /**
     * Get the goal that owns the milestone.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get all todos for this milestone.
     */
    public function todos(): HasMany
    {
        return $this->hasMany(MilestoneTodo::class)->orderBy('sort_order');
    }

    /**
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }

    /**
     * Mark milestone as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        // Update parent goal progress based on milestones
        $this->updateGoalProgress();
    }

    /**
     * Mark milestone as incomplete.
     */
    public function markAsIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);

        $this->updateGoalProgress();
    }

    /**
     * Update parent goal progress based on completed milestones.
     * Note: Soft milestones (is_soft = true) are excluded from progress calculation.
     */
    protected function updateGoalProgress(): void
    {
        $goal = $this->goal;
        // Only count non-soft milestones for progress calculation
        $totalMilestones = $goal->milestones()->where('is_soft', false)->count();

        if ($totalMilestones === 0) {
            return;
        }

        $completedMilestones = $goal->milestones()
            ->where('is_soft', false)
            ->where('is_completed', true)
            ->count();
        $progress = (int) round(($completedMilestones / $totalMilestones) * 100);

        $goal->update(['progress' => $progress]);
    }

    /**
     * Scope for completed milestones.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope for pending milestones.
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope for overdue milestones.
     */
    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope for soft milestones (reminders, not counted in progress).
     */
    public function scopeSoft($query)
    {
        return $query->where('is_soft', true);
    }

    /**
     * Scope for regular milestones (counted in progress).
     */
    public function scopeRegular($query)
    {
        return $query->where('is_soft', false);
    }
}
