<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        CREATE TABLE customer_accounts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_code VARCHAR(30) NOT NULL UNIQUE,
            full_name VARCHAR(150) NOT NULL,
            mobile_country_code VARCHAR(10) NOT NULL DEFAULT '+91',
            mobile_number VARCHAR(20) NOT NULL,
            email VARCHAR(150) NULL,
            alternate_mobile_country_code VARCHAR(10) NULL,
            alternate_mobile_number VARCHAR(20) NULL,
            address_line_1 VARCHAR(255) NULL,
            address_line_2 VARCHAR(255) NULL,
            city VARCHAR(100) NULL,
            district VARCHAR(100) NULL,
            state VARCHAR(100) NULL,
            postal_code VARCHAR(20) NULL,
            profile_photo VARCHAR(500) NULL,
            status ENUM('ACTIVE', 'INACTIVE', 'SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
            email_verified_at DATETIME NULL,
            mobile_verified_at DATETIME NULL,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            UNIQUE KEY uq_customer_mobile (mobile_country_code, mobile_number),
            UNIQUE KEY uq_customer_email (email),
            INDEX idx_customer_status (status),
            INDEX idx_customer_name (full_name)
        ) ENGINE=InnoDB;
        SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};
