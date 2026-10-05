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
CREATE TABLE app_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    platform ENUM('ANDROID','IOS') NOT NULL,
    version_name VARCHAR(50) NOT NULL,
    version_code VARCHAR(50) NOT NULL,
    minimum_supported BOOLEAN NOT NULL DEFAULT FALSE,
    store_url VARCHAR(500) NULL,
    release_notes LONGTEXT NULL,
    released_at DATETIME NULL,
    status ENUM('DRAFT','PUBLISHED','RETIRED') NOT NULL DEFAULT 'DRAFT',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_app_platform_version (platform, version_name, version_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
