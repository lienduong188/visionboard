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
        // MODIFY COLUMN is MySQL-only syntax; SQLite uses TEXT for string/enum columns anyway
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reminders MODIFY COLUMN type ENUM('progress', 'deadline', 'custom') DEFAULT 'progress'");
        }

        // Add new columns (guard against re-running on partially migrated DB)
        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('reminders', 'weekly_days')) {
                $table->string('weekly_days', 20)->nullable()->after('frequency');
            }
            if (!Schema::hasColumn('reminders', 'monthly_day')) {
                $table->tinyInteger('monthly_day')->nullable()->after('weekly_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reminders MODIFY COLUMN type ENUM('email', 'push', 'both') DEFAULT 'both'");
        }

        Schema::table('reminders', function (Blueprint $table) {
            $cols = array_filter(['weekly_days', 'monthly_day'], fn($col) => Schema::hasColumn('reminders', $col));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
