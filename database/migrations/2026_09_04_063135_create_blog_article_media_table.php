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
        Schema::create('blog_article_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_article_id')->constrained('blog_articles')->cascadeOnDelete();
            $table->string('media_type', 50);
            $table->string('url', 1000)->nullable();
            $table->string('storage_key', 500)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['blog_article_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_article_media');
    }
};
