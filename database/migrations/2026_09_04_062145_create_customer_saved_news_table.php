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
CREATE TABLE customer_saved_news (
    customer_account_id BIGINT UNSIGNED NOT NULL,
    news_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (customer_account_id, news_id),
    CONSTRAINT fk_saved_news_customer FOREIGN KEY (customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_news_news FOREIGN KEY (news_id)
        REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_saved_news');
    }
};
