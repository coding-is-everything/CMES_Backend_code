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
        Schema::create('support_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_request_id')->constrained('support_requests')->cascadeOnDelete();
            $table->string('sender_type', 30);
            $table->unsignedBigInteger('sender_id');
            $table->longText('message');
            $table->timestamps();
            $table->index(['support_request_id', 'created_at']);
            $table->index(['sender_type', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_request_messages');
    }
};
