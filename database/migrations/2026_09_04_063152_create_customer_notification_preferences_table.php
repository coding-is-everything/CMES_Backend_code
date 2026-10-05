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
CREATE TABLE customer_notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    push_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    renewal_alerts BOOLEAN NOT NULL DEFAULT TRUE,
    document_alerts BOOLEAN NOT NULL DEFAULT TRUE,
    compliance_alerts BOOLEAN NOT NULL DEFAULT TRUE,
    project_updates BOOLEAN NOT NULL DEFAULT TRUE,
    news_alerts BOOLEAN NOT NULL DEFAULT TRUE,
    blog_alerts BOOLEAN NOT NULL DEFAULT TRUE,
    community_alerts BOOLEAN NOT NULL DEFAULT TRUE,
    quiet_hours_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY uq_notification_pref_customer (customer_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_notification_preferences');
    }
};
