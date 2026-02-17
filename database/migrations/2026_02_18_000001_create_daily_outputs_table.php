<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('goal_id')->nullable()->constrained()->onDelete('set null');
            $table->date('output_date');
            $table->string('title', 255);
            $table->string('category', 50);
            $table->unsignedSmallInteger('duration')->default(60);
            $table->text('note')->nullable();
            $table->string('output_link', 500)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->enum('status', ['planned', 'done', 'skipped'])->default('planned');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'output_date']);
            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_outputs');
    }
};
