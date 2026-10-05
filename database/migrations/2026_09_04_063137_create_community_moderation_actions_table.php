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
        Schema::create('community_moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->nullable()->constrained('community_discussions')->nullOnDelete();
            $table->foreignId('reply_id')->nullable()->constrained('community_replies')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->index(['discussion_id', 'created_at']);
            $table->index(['reply_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_moderation_actions');
    }
};
