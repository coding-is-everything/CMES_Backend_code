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
        Schema::create('push_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_notification_id')->constrained('push_notifications')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('user_devices')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_message_id', 255)->nullable();
            $table->string('status', 50);
            $table->string('error_code', 100)->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->timestamps();
            $table->index(['push_notification_id', 'status']);
            $table->index(['device_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_delivery_logs');
    }
};
