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
CREATE TABLE support_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    ticket_code VARCHAR(50) NOT NULL UNIQUE,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    assigned_admin_id BIGINT UNSIGNED NULL,
    subject VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    priority ENUM('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
    status ENUM('OPEN','IN_PROGRESS','WAITING_FOR_CUSTOMER','RESOLVED','CLOSED') NOT NULL DEFAULT 'OPEN',
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES support_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_ticket_customer (customer_account_id),
    INDEX idx_ticket_project (project_id),
    INDEX idx_ticket_status (status),
    INDEX idx_ticket_assigned (assigned_admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
