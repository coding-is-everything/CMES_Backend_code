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
        Schema::create('project_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('date_type', 100);
            $table->string('label', 255)->nullable();
            $table->date('date_value');
            $table->string('status', 30)->default('ACTIVE');
            $table->boolean('is_important')->default(false);
            $table->string('source_reference', 255)->nullable();
            $table->timestamps();
            $table->index(['project_id', 'date_value']);
            $table->index(['project_id', 'is_important']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_dates');
    }
};
