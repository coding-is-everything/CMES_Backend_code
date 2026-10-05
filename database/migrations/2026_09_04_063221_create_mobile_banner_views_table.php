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
CREATE TABLE mobile_banner_views (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    banner_id BIGINT UNSIGNED NOT NULL,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    clicked_at DATETIME NULL,

    PRIMARY KEY (id),
    FOREIGN KEY (banner_id) REFERENCES mobile_banners(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    INDEX idx_banner_view_banner (banner_id),
    INDEX idx_banner_view_customer (customer_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_banner_views');
    }
};
