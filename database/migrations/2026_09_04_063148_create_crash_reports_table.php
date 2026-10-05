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
        Schema::create('crash_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anonymous_id', 150)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('build_number', 50)->nullable();
            $table->string('platform', 30)->nullable();
            $table->string('device_model', 150)->nullable();
            $table->string('os_version', 100)->nullable();
            $table->string('error_type', 150)->nullable();
            $table->text('message')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->dateTime('created_at')->useCurrent();
            $table->index('occurred_at');
            $table->index(['platform', 'app_version']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crash_reports');
    }
};
