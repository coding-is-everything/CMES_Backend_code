<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        CREATE TABLE customer_devices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_account_id BIGINT UNSIGNED NOT NULL,
            device_uuid VARCHAR(255) NOT NULL,
            platform ENUM('ANDROID', 'IOS', 'WEB') NOT NULL,
            push_token VARCHAR(500) NOT NULL,
            device_name VARCHAR(150) NULL,
            app_version VARCHAR(50) NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            last_seen_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_device_customer FOREIGN KEY (customer_account_id)
            REFERENCES customer_accounts(id) ON DELETE CASCADE,
            UNIQUE KEY uq_customer_device (customer_account_id, device_uuid),
            INDEX idx_device_push_token (push_token),
            INDEX idx_device_active (is_active)
        ) ENGINE=InnoDB;
        SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_devices');
    }
};
