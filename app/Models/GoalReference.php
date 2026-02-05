<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GoalReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'type',
        'title',
        'content',
        'file_path',
        'file_name',
        'file_size',
        'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['file_url'];

    /**
     * Get the goal that owns the reference.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get file URL attribute.
     */
    public function getFileUrlAttribute(): ?string
    {
        if ($this->type === 'file' && $this->file_path) {
            return Storage::url($this->file_path);
        }
        return null;
    }

    /**
     * Format file size for display.
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
