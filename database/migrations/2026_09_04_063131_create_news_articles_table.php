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
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('news_category_id')->constrained('news_categories')->restrictOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->unsignedInteger('reading_time_minutes')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('status', 30)->default('DRAFT');
            $table->dateTime('published_at')->nullable();
            $table->string('author_name', 150)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
            $table->index(['featured', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
