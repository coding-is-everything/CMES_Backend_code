<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Schema derived from the CMES Mobile "Schema / Key Fields" catalogue
     * and supplied relationship/dependency map.
     */
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('device_uuid')->unique();
            $table->enum('platform', ['ANDROID', 'IOS', 'WEB', 'OTHER']);
            $table->string('app_version', 50)->nullable();
            $table->string('build_number', 50)->nullable();
            $table->string('push_provider', 50)->nullable();
            $table->text('push_token')->nullable();
            $table->string('permission_status', 30)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('registered_at')->nullable();
            $table->dateTime('unregistered_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'platform']);
            $table->index('push_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
