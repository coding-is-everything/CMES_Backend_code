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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anonymous_id', 150)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('event_name', 150);
            $table->json('event_properties')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('build_number', 50)->nullable();
            $table->string('platform', 30)->nullable();
            $table->dateTime('occurred_at');
            $table->dateTime('created_at')->useCurrent();
            $table->index(['event_name', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['project_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
