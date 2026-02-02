<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('review_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('weekly_enabled')->default(false);
            $table->boolean('monthly_enabled')->default(false);
            $table->tinyInteger('weekly_day')->default(1); // 1=Monday, 7=Sunday
            $table->tinyInteger('monthly_day')->default(1); // Day of month (1-28)
            $table->time('send_time')->default('09:00:00');
            $table->timestamp('last_weekly_sent_at')->nullable();
            $table->timestamp('last_monthly_sent_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_settings');
    }
};
