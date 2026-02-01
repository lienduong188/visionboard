<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'title',
        'description',
        'target_value',
        'due_date',
        'completed_at',
        'is_completed',
        'sort_order',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    /**
     * Get the goal that owns the milestone.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
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
     */
    protected function updateGoalProgress(): void
    {
        $goal = $this->goal;
        $totalMilestones = $goal->milestones()->count();

        if ($totalMilestones === 0) {
            return;
        }

        $completedMilestones = $goal->milestones()->where('is_completed', true)->count();
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
}
