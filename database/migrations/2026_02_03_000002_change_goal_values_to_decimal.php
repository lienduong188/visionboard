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
        Schema::table('goals', function (Blueprint $table) {
            $table->decimal('target_value', 10, 2)->nullable()->change();
            $table->decimal('current_value', 10, 2)->nullable()->default(0)->change();
            $table->decimal('start_value', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->integer('target_value')->nullable()->change();
            $table->integer('current_value')->nullable()->change();
            $table->integer('start_value')->nullable()->change();
        });
    }
};
