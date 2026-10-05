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
CREATE TABLE community_mentions (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    mentioned_customer_account_id BIGINT UNSIGNED NOT NULL,
    actor_customer_account_id BIGINT UNSIGNED NOT NULL,
    discussion_id BIGINT UNSIGNED NULL,
    reply_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (mentioned_customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (discussion_id) REFERENCES community_discussions(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_id) REFERENCES community_replies(id) ON DELETE CASCADE,
    INDEX idx_mention_target (mentioned_customer_account_id),
    INDEX idx_mention_discussion (discussion_id),
    INDEX idx_mention_reply (reply_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_mentions');
    }
};
