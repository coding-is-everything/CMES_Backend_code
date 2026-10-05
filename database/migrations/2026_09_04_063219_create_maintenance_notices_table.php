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
CREATE TABLE maintenance_notices (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message LONGTEXT NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    severity ENUM('INFO','WARNING','CRITICAL') NOT NULL DEFAULT 'WARNING',
    status ENUM('DRAFT','SCHEDULED','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_maintenance_status (status),
    INDEX idx_maintenance_window (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_notices');
    }
};
