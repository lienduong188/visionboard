<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update type enum to reminder purpose (progress, deadline, custom)
        DB::statement("ALTER TABLE reminders MODIFY COLUMN type ENUM('progress', 'deadline', 'custom') DEFAULT 'progress'");

        // Add new columns for better frequency handling (like Google Calendar)
        Schema::table('reminders', function (Blueprint $table) {
            // For weekly: which days (1=Mon, 7=Sun), e.g., "1,3,5" for Mon/Wed/Fri
            $table->string('weekly_days', 20)->nullable()->after('frequency');
            // For monthly: which day of month (1-31), e.g., "1" for 1st, "15" for 15th
            $table->tinyInteger('monthly_day')->nullable()->after('weekly_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE reminders MODIFY COLUMN type ENUM('email', 'push', 'both') DEFAULT 'both'");

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['weekly_days', 'monthly_day']);
        });
    }
};
