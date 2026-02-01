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
        Schema::create('progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->onDelete('cascade');
            $table->decimal('previous_value', 15, 2)->nullable();
            $table->decimal('new_value', 15, 2)->nullable();
            $table->integer('previous_progress')->nullable();
            $table->integer('new_progress')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('logged_at')->useCurrent();

            $table->index('goal_id');
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_logs');
    }
};
