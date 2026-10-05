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
        Schema::create('api_sync_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope', 100);
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('resource', 150);
            $table->dateTime('last_synced_at')->nullable();
            $table->string('etag', 255)->nullable();
            $table->dateTime('last_success_at')->nullable();
            $table->dateTime('last_error_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'scope', 'project_id', 'resource'], 'uq_api_sync_user_scope_project_resource');
            $table->index(['project_id', 'resource']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_sync_states');
    }
};
