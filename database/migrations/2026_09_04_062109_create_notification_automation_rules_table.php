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
CREATE TABLE notification_automation_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(150) NOT NULL,
    notification_template_id BIGINT UNSIGNED NOT NULL,
    trigger_type ENUM('LEASE_EXPIRY','RENEWAL_DUE','DOCUMENT_EXPIRY') NOT NULL,
    trigger_days_before INT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_executed_at DATETIME NULL,
    next_execution_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rule_template FOREIGN KEY (notification_template_id)
        REFERENCES notification_templates(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_automation_rules');
    }
};
