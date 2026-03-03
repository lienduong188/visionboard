<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động backup database hàng ngày lúc 2 giờ sáng (Asia/Tokyo)
Schedule::command('backup:run')->dailyAt('02:00');
