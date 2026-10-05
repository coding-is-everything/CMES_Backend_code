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
CREATE TABLE project_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    lease_id BIGINT UNSIGNED NULL,
    transfer_reference VARCHAR(100) NOT NULL UNIQUE,
    from_proprietor_id BIGINT UNSIGNED NULL,
    to_proprietor_id BIGINT UNSIGNED NULL,
    from_customer_account_id BIGINT UNSIGNED NULL,
    to_customer_account_id BIGINT UNSIGNED NULL,
    transfer_type ENUM('PROJECT_TRANSFER','LEASE_TRANSFER','OWNERSHIP_TRANSFER','OTHER') NOT NULL,
    transfer_date DATE NOT NULL,
    remaining_lease_start_date DATE NULL,
    remaining_lease_end_date DATE NULL,
    transfer_reason TEXT NULL,
    transfer_status ENUM('DRAFT','PENDING','APPROVED','REJECTED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transfer_project FOREIGN KEY (project_id)
        REFERENCES projects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transfer_lease FOREIGN KEY (lease_id)
        REFERENCES leases(id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_from_proprietor FOREIGN KEY (from_proprietor_id)
        REFERENCES proprietors(id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_to_proprietor FOREIGN KEY (to_proprietor_id)
        REFERENCES proprietors(id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_from_customer FOREIGN KEY (from_customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_to_customer FOREIGN KEY (to_customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE SET NULL,
    INDEX idx_transfer_project (project_id),
    INDEX idx_transfer_status (transfer_status),
    INDEX idx_transfer_date (transfer_date)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_transfers');
    }
};
