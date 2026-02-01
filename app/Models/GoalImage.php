<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GoalImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'image_path',
        'caption',
        'is_cover',
        'sort_order',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
    ];

    /**
     * Get the goal that owns the image.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get the full URL of the image.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    /**
     * Set this image as cover and unset others.
     */
    public function setAsCover(): void
    {
        // Unset other cover images for this goal
        self::where('goal_id', $this->goal_id)
            ->where('id', '!=', $this->id)
            ->update(['is_cover' => false]);

        $this->update(['is_cover' => true]);

        // Update goal's cover_image
        $this->goal->update(['cover_image' => $this->image_path]);
    }

    /**
     * Scope for cover images.
     */
    public function scopeCover($query)
    {
        return $query->where('is_cover', true);
    }
}
