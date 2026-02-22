<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyOutput extends Model
{
    use HasFactory;

    // impact: Tác động dài hạn (1-10)
    // compound: Khả năng tích lũy theo thời gian (1-10)
    // flywheel = impact × compound (max 100)
    const CATEGORIES = [
        'coding'   => ['icon' => '💻', 'label' => 'Coding',   'impact' => 9, 'compound' => 9],
        'writing'  => ['icon' => '✍️', 'label' => 'Writing',  'impact' => 8, 'compound' => 9],
        'career'   => ['icon' => '💼', 'label' => 'Career',   'impact' => 9, 'compound' => 7],
        'study'    => ['icon' => '📚', 'label' => 'Study',    'impact' => 7, 'compound' => 8],
        'video'    => ['icon' => '🎥', 'label' => 'Video',    'impact' => 7, 'compound' => 7],
        'training' => ['icon' => '🏃', 'label' => 'Training', 'impact' => 8, 'compound' => 5],
        'creative' => ['icon' => '🎨', 'label' => 'Creative', 'impact' => 6, 'compound' => 6],
        'wellness' => ['icon' => '🧘', 'label' => 'Wellness', 'impact' => 7, 'compound' => 5],
        'other'    => ['icon' => '🔧', 'label' => 'Other',    'impact' => 4, 'compound' => 3],
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
