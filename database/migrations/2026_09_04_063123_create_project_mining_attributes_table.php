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
        Schema::create('project_mining_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('mineral_type', 150)->nullable();
            $table->string('mining_method', 150)->nullable();
            $table->decimal('lease_area', 18, 4)->nullable();
            $table->string('lease_area_unit', 30)->nullable();
            $table->decimal('production_capacity', 18, 4)->nullable();
            $table->string('production_capacity_unit', 30)->nullable();
            $table->string('mining_lease_number', 150)->nullable();
            $table->string('environmental_clearance_number', 150)->nullable();
            $table->json('other_attributes')->nullable();
            $table->timestamps();
            $table->unique('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_mining_attributes');
    }
};
