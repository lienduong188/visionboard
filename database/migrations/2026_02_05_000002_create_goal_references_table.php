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
        Schema::create('goal_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['link', 'file', 'text']);
            $table->string('title', 255);
            $table->text('content')->nullable(); // URL for link, text content for text
            $table->string('file_path', 500)->nullable(); // storage path for files
            $table->string('file_name', 255)->nullable(); // original filename
            $table->unsignedInteger('file_size')->nullable(); // file size in bytes
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('goal_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_references');
    }
};
