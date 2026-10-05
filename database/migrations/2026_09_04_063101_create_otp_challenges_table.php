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
        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_identity_id')->constrained('user_identities')->cascadeOnDelete();
            $table->string('channel', 30);
            $table->string('challenge_hash', 255);
            $table->dateTime('expires_at');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->unsignedInteger('resend_count')->default(0);
            $table->dateTime('verified_at')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->timestamps();
            $table->index(['user_identity_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_challenges');
    }
};
