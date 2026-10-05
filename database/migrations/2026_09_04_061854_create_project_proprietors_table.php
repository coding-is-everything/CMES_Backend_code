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
CREATE TABLE project_proprietors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    proprietor_id BIGINT UNSIGNED NOT NULL,
    relationship_type ENUM('CURRENT','PREVIOUS','TRANSFEROR','TRANSFEREE') NOT NULL DEFAULT 'CURRENT',
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    is_current BOOLEAN NOT NULL DEFAULT TRUE,
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pp_project FOREIGN KEY (project_id)
        REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_proprietor FOREIGN KEY (proprietor_id)
        REFERENCES proprietors(id) ON DELETE RESTRICT,
    INDEX idx_pp_project (project_id),
    INDEX idx_pp_proprietor (proprietor_id),
    INDEX idx_pp_current (is_current)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_proprietors');
    }
};
