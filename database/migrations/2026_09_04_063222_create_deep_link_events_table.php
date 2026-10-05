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
CREATE TABLE deep_link_events (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    notification_id BIGINT UNSIGNED NULL,
    source_type VARCHAR(50) NOT NULL,
    source_reference VARCHAR(255) NULL,
    destination_route VARCHAR(255) NOT NULL,
    payload_json JSON NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    INDEX idx_deep_link_customer (customer_account_id),
    INDEX idx_deep_link_project (project_id),
    INDEX idx_deep_link_opened (opened_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deep_link_events');
    }
};
