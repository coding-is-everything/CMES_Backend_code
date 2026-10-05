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
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 100);
            $table->string('version', 50);
            $table->string('title', 255);
            $table->longText('content');
            $table->string('status', 30)->default('DRAFT');
            $table->dateTime('effective_at')->nullable();
            $table->timestamps();
            $table->unique(['document_type', 'version']);
            $table->index(['document_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
