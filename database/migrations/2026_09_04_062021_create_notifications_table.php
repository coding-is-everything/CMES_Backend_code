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
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_template_id BIGINT UNSIGNED NULL,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    scope ENUM('ACCOUNT','PROJECT') NOT NULL,
    notification_type ENUM('RENEWAL','DOCUMENT','COMPLIANCE','PROJECT_UPDATE','NEWS','BLOG','COMMUNITY','SYSTEM','OTHER') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
    action_type VARCHAR(50) NULL,
    action_reference VARCHAR(100) NULL,
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    status ENUM('DRAFT','SCHEDULED','SENT','FAILED') NOT NULL DEFAULT 'DRAFT',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_template FOREIGN KEY (notification_template_id)
        REFERENCES notification_templates(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_customer FOREIGN KEY (customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_project FOREIGN KEY (project_id)
        REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_notification_customer (customer_account_id),
    INDEX idx_notification_project (project_id),
    INDEX idx_notification_status (status),
    INDEX idx_notification_scheduled (scheduled_at)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
