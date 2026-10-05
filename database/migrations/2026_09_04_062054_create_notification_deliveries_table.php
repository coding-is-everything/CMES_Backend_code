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
CREATE TABLE notification_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    customer_device_id BIGINT UNSIGNED NOT NULL,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    opened_at DATETIME NULL,
    status ENUM('PENDING','SENT','DELIVERED','OPENED','FAILED') NOT NULL DEFAULT 'PENDING',
    failure_reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_delivery_notification FOREIGN KEY (notification_id)
        REFERENCES notifications(id) ON DELETE CASCADE,
    CONSTRAINT fk_delivery_device FOREIGN KEY (customer_device_id)
        REFERENCES customer_devices(id) ON DELETE CASCADE,
    INDEX idx_delivery_notification (notification_id),
    INDEX idx_delivery_status (status)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
