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
CREATE TABLE news_views (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    news_id BIGINT UNSIGNED NOT NULL,
    first_viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    view_count INT UNSIGNED NOT NULL DEFAULT 1,

    PRIMARY KEY (id),
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    UNIQUE KEY uq_news_view (customer_account_id, news_id),
    INDEX idx_news_view_last (last_viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_views');
    }
};
