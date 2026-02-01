<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'goal_id',
        'previous_value',
        'new_value',
        'previous_progress',
        'new_progress',
        'note',
        'logged_at',
    ];

    protected $casts = [
        'previous_value' => 'decimal:2',
        'new_value' => 'decimal:2',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the goal that owns this log.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get the change in value.
     */
    public function getValueChangeAttribute(): float
    {
        return ($this->new_value ?? 0) - ($this->previous_value ?? 0);
    }

    /**
     * Get the change in progress.
     */
    public function getProgressChangeAttribute(): int
    {
        return ($this->new_progress ?? 0) - ($this->previous_progress ?? 0);
    }

    /**
     * Boot method to set default logged_at.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->logged_at) {
                $model->logged_at = now();
            }
        });
    }
}
