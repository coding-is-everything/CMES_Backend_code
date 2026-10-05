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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('document_category_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->string('title', 255);
            $table->string('document_number', 150)->nullable();
            $table->string('file_name', 255);
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('storage_key', 500);
            $table->unsignedInteger('version')->default(1);
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->boolean('is_downloadable')->default(true);
            $table->boolean('is_shareable')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
