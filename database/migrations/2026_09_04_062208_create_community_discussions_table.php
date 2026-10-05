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
CREATE TABLE community_discussions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_code VARCHAR(50) NOT NULL UNIQUE,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('PUBLISHED','PENDING','HIDDEN','LOCKED','ARCHIVED') NOT NULL DEFAULT 'PUBLISHED',
    reply_count INT UNSIGNED NOT NULL DEFAULT 0,
    like_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_discussion_customer FOREIGN KEY (customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE RESTRICT,
    CONSTRAINT fk_discussion_category FOREIGN KEY (category_id)
        REFERENCES community_categories(id) ON DELETE RESTRICT,
    INDEX idx_discussion_customer (customer_account_id),
    INDEX idx_discussion_category (category_id),
    INDEX idx_discussion_status (status)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_discussions');
    }
};
