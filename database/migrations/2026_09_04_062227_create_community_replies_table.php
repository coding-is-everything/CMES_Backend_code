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
CREATE TABLE community_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id BIGINT UNSIGNED NOT NULL,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    parent_reply_id BIGINT UNSIGNED NULL,
    content TEXT NOT NULL,
    status ENUM('PUBLISHED','HIDDEN','DELETED') NOT NULL DEFAULT 'PUBLISHED',
    like_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reply_discussion FOREIGN KEY (discussion_id)
        REFERENCES community_discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_reply_customer FOREIGN KEY (customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE RESTRICT,
    CONSTRAINT fk_reply_parent FOREIGN KEY (parent_reply_id)
        REFERENCES community_replies(id) ON DELETE CASCADE,
    INDEX idx_reply_discussion (discussion_id),
    INDEX idx_reply_parent (parent_reply_id)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_replies');
    }
};
