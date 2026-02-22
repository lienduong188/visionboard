<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyOutput extends Model
{
    use HasFactory;

    const CATEGORIES = [
        'coding'   => ['icon' => '💻', 'label' => 'Coding'],
        'writing'  => ['icon' => '✍️', 'label' => 'Writing'],
        'video'    => ['icon' => '🎥', 'label' => 'Video'],
        'study'    => ['icon' => '📚', 'label' => 'Study'],
        'training' => ['icon' => '🏃', 'label' => 'Training'],
        'creative' => ['icon' => '🎨', 'label' => 'Creative'],
        'career'   => ['icon' => '💼', 'label' => 'Career'],
        'wellness' => ['icon' => '🧘', 'label' => 'Wellness'],
        'other'    => ['icon' => '🔧', 'label' => 'Other'],
    ];

    const DURATION_PRESETS = [30, 60, 90, 120];

    const TRACKING_START = '2026-02-17';
    const TRACKING_END = '2027-02-05';

    protected $fillable = [
        'user_id',
        'goal_id',
        'output_date',
        'title',
        'category',
        'duration',
        'note',
        'output_link',
        'image_path',
        'rating',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'output_date' => 'string',
        'duration' => 'integer',
        'rating' => 'integer',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('output_date', $date);
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }
}
