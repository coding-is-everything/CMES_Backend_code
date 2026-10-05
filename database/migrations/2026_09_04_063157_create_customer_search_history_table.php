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
CREATE TABLE customer_search_history (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    search_term VARCHAR(255) NOT NULL,
    search_scope ENUM('ALL','NEWS','BLOGS','COMMUNITY') NOT NULL DEFAULT 'ALL',
    searched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    INDEX idx_search_customer (customer_account_id),
    INDEX idx_search_term (search_term(100)),
    INDEX idx_search_date (searched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_search_history');
    }
};
