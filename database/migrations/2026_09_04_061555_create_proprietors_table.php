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
CREATE TABLE proprietors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proprietor_code VARCHAR(30) NOT NULL UNIQUE,
    proprietor_name VARCHAR(200) NOT NULL,
    entity_type ENUM('INDIVIDUAL','PROPRIETORSHIP','PARTNERSHIP','PRIVATE_LIMITED','PUBLIC_LIMITED','LLP','TRUST','GOVERNMENT','OTHER') NOT NULL DEFAULT 'INDIVIDUAL',
    contact_person VARCHAR(150) NULL,
    mobile_country_code VARCHAR(10) NULL,
    mobile_number VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    address_line_1 VARCHAR(255) NULL,
    address_line_2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    registration_number VARCHAR(100) NULL,
    pan_number VARCHAR(20) NULL,
    gst_number VARCHAR(30) NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_proprietor_name (proprietor_name),
    INDEX idx_proprietor_status (status)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proprietors');
    }
};
