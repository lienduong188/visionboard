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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 500)->nullable()->after('email');
            $table->string('timezone', 50)->default('Asia/Tokyo')->after('avatar');
            $table->boolean('notification_email')->default(true)->after('timezone');
            $table->boolean('notification_push')->default(true)->after('notification_email');
            $table->string('theme', 20)->default('light')->after('notification_push');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'timezone', 'notification_email', 'notification_push', 'theme']);
        });
    }
};
