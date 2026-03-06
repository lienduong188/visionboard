<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_outputs', function (Blueprint $table) {
            $table->string('movement_type', 50)->nullable()->after('category');
            $table->decimal('distance_km', 8, 2)->nullable()->after('movement_type');
            $table->string('duration_hms', 10)->nullable()->after('distance_km'); // HH:MM:SS
            $table->unsignedSmallInteger('heart_rate')->nullable()->after('duration_hms');
            $table->unsignedSmallInteger('cadence')->nullable()->after('heart_rate');
            $table->unsignedMediumInteger('kcal')->nullable()->after('cadence');
        });
    }

    public function down(): void
    {
        Schema::table('daily_outputs', function (Blueprint $table) {
            $table->dropColumn(['movement_type', 'distance_km', 'duration_hms', 'heart_rate', 'cadence', 'kcal']);
        });
    }
};
