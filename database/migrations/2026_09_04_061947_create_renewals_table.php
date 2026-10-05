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
CREATE TABLE renewals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    lease_id BIGINT UNSIGNED NULL,
    renewal_reference VARCHAR(100) NOT NULL UNIQUE,
    current_expiry_date DATE NOT NULL,
    renewal_due_date DATE NOT NULL,
    application_date DATE NULL,
    submission_date DATE NULL,
    approval_date DATE NULL,
    status ENUM('NOT_STARTED','UPCOMING','IN_PROGRESS','SUBMITTED','APPROVED','REJECTED','EXPIRED') NOT NULL DEFAULT 'NOT_STARTED',
    assigned_admin_id BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_renewal_project FOREIGN KEY (project_id)
        REFERENCES projects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_renewal_lease FOREIGN KEY (lease_id)
        REFERENCES leases(id) ON DELETE SET NULL,
    INDEX idx_renewal_project (project_id),
    INDEX idx_renewal_due (renewal_due_date),
    INDEX idx_renewal_status (status)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewals');
    }
};
