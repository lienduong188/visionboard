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
        'writing'  => ['icon' => '✍️', 'label' => 'Writing',  'impact' => 8, 'compound' => 9, 'tooltip' => 'Viết blog, caption, scripting'],
        'capture'  => ['icon' => '📸', 'label' => 'Capture',  'impact' => 7, 'compound' => 7, 'tooltip' => 'Quay vlog, chụp ảnh (raw, chưa edit)'],
        'edit'     => ['icon' => '🎬', 'label' => 'Edit',     'impact' => 8, 'compound' => 8, 'tooltip' => 'Edit video, audio mixing, post-production'],
        'art'      => ['icon' => '🖌️', 'label' => 'Art',      'impact' => 7, 'compound' => 7, 'tooltip' => 'Vẽ tranh, design Adobe (Illustrator, Photoshop...)'],
        'craft'    => ['icon' => '🧶', 'label' => 'Craft',    'impact' => 5, 'compound' => 4, 'tooltip' => 'Thêu thùa, móc len, handmade'],
        'movement' => ['icon' => '🏃', 'label' => 'Movement', 'impact' => 8, 'compound' => 6, 'tooltip' => 'Chạy, gym, leo núi'],
        'learning' => ['icon' => '📚', 'label' => 'Learning', 'impact' => 8, 'compound' => 9, 'tooltip' => 'Đọc sách, học, coding'],
        'connect'  => ['icon' => '🤝', 'label' => 'Connect',  'impact' => 7, 'compound' => 6, 'tooltip' => 'Gọi cho gia đình, kết nối bạn bè'],
    ];

    const DURATION_PRESETS = [10, 30, 60, 90, 120];

    const MOVEMENT_TYPES = [
        'running'       => ['label' => 'Running',      'ja' => 'ランニング',    'icon' => '🏃'],
        'trail_running' => ['label' => 'Trail Running', 'ja' => 'トレイルラン', 'icon' => '🏔️'],
        'gym'           => ['label' => 'Gym',           'ja' => 'ジム',          'icon' => '🏋️'],
        'hiking'        => ['label' => 'Hiking',        'ja' => 'ハイキング',    'icon' => '⛰️'],
        'other'         => ['label' => 'Other',         'ja' => 'その他',        'icon' => '⚡'],
    ];

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
        'images',
        'rating',
        'status',
        'sort_order',
        'movement_type',
        'distance_km',
        'duration_hms',
        'heart_rate',
        'cadence',
        'kcal',
    ];

    protected $casts = [
        'output_date' => 'string',
        'duration' => 'integer',
        'rating' => 'integer',
        'sort_order' => 'integer',
        'images' => 'array',
        'distance_km' => 'decimal:2',
        'heart_rate' => 'integer',
        'cadence' => 'integer',
        'kcal' => 'integer',
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
