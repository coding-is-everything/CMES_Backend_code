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
CREATE TABLE community_discussion_likes (
    discussion_id BIGINT UNSIGNED NOT NULL,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (discussion_id, customer_account_id),
    CONSTRAINT fk_discussion_like_discussion FOREIGN KEY (discussion_id)
        REFERENCES community_discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_discussion_like_customer FOREIGN KEY (customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_discussion_likes');
    }
};
