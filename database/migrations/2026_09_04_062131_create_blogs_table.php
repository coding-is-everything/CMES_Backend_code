<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Source: CMES supplied CREATE TABLE specification.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE blogs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blog_code VARCHAR(50) NOT NULL UNIQUE,
    category_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(300) NOT NULL UNIQUE,
    short_description TEXT NULL,
    featured_image VARCHAR(500) NULL,
    content LONGTEXT NOT NULL,
    author_name VARCHAR(150) NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(500) NULL,
    status ENUM('DRAFT','SCHEDULED','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
    scheduled_at DATETIME NULL,
    published_at DATETIME NULL,
    view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_blog_category FOREIGN KEY (category_id)
        REFERENCES blog_categories(id) ON DELETE RESTRICT,
    INDEX idx_blog_status (status),
    INDEX idx_blog_published (published_at)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
