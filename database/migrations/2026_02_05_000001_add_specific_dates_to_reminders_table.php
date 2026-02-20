<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('reminders', 'custom_days')) {
                $table->string('custom_days', 20)->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('reminders', 'specific_dates')) {
                $table->text('specific_dates')->nullable()->after('custom_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $cols = array_filter(['custom_days', 'specific_dates'], fn($c) => Schema::hasColumn('reminders', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
