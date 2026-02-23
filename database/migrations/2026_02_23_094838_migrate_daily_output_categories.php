<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $map = [
        'coding'   => 'learning',
        'career'   => 'learning',
        'study'    => 'learning',
        'video'    => 'edit',
        'training' => 'movement',
        'creative' => 'art',
        'wellness' => 'connect',
        'other'    => 'learning',
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('daily_outputs')
                ->where('category', $old)
                ->update(['category' => $new]);
        }
    }

    public function down(): void
    {
        // Không thể reverse hoàn toàn vì nhiều categories gộp vào 1
        // (coding/career/study/other → learning)
    }
};
