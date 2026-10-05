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
CREATE TABLE leases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    lease_number VARCHAR(100) NOT NULL,
    lease_start_date DATE NOT NULL,
    lease_end_date DATE NOT NULL,
    lease_period_years DECIMAL(8,2) NULL,
    lease_area DECIMAL(15,4) NULL,
    lease_area_unit VARCHAR(20) NOT NULL DEFAULT 'HECTARE',
    lease_status ENUM('ACTIVE','EXPIRED','SUSPENDED','TRANSFERRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
    issuing_authority VARCHAR(200) NULL,
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lease_project FOREIGN KEY (project_id)
        REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY uq_lease_number (lease_number),
    INDEX idx_lease_project (project_id),
    INDEX idx_lease_end_date (lease_end_date),
    INDEX idx_lease_status (lease_status)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
