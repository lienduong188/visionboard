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
        Schema::table('reminders', function (Blueprint $table) {
            // Add specific_dates column for storing specific dates like "2026-03-15,2026-05-20"
            $table->text('specific_dates')->nullable()->after('custom_days');
        });

        // Update frequency enum to include 'specific' instead of 'custom'
        DB::statement("ALTER TABLE reminders MODIFY COLUMN frequency ENUM('daily', 'weekly', 'monthly', 'specific') DEFAULT 'weekly'");

        // Migrate existing 'custom' frequency to 'weekly' (since custom_days was same as weekly)
        DB::table('reminders')->where('frequency', 'custom')->update(['frequency' => 'weekly']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert frequency enum
        DB::statement("ALTER TABLE reminders MODIFY COLUMN frequency ENUM('daily', 'weekly', 'monthly', 'custom') DEFAULT 'weekly'");

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('specific_dates');
        });
    }
};
