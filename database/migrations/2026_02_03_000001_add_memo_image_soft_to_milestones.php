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
        Schema::table('milestones', function (Blueprint $table) {
            $table->text('memo')->nullable()->after('description');
            $table->string('image_path', 500)->nullable()->after('memo');
            $table->boolean('is_soft')->default(false)->after('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropColumn(['memo', 'image_path', 'is_soft']);
        });
    }
};
