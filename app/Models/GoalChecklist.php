<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'title',
        'is_completed',
        'sort_order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    /**
     * Get the goal that owns the checklist item.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Scope for completed items.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope for pending items.
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }
}
