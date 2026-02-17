<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutputRestDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rest_date',
        'is_earned',
    ];

    protected $casts = [
        'rest_date' => 'date',
        'is_earned' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
