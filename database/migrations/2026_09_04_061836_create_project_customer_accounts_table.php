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
CREATE TABLE project_customer_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    relationship_type ENUM('PRIMARY','AUTHORIZED_CONTACT','REPRESENTATIVE','OTHER') NOT NULL DEFAULT 'PRIMARY',
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    effective_from DATE NULL,
    effective_to DATE NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pca_project FOREIGN KEY (project_id)
        REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_pca_customer FOREIGN KEY (customer_account_id)
        REFERENCES customer_accounts(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_project_customer (project_id, customer_account_id),
    INDEX idx_pca_customer (customer_account_id),
    INDEX idx_pca_project (project_id)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_customer_accounts');
    }
};
