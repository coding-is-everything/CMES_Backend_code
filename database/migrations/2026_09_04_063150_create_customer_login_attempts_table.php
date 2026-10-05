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
CREATE TABLE customer_login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    customer_account_id BIGINT UNSIGNED NULL,
    login_identifier VARCHAR(255) NOT NULL,
    identifier_type ENUM('MOBILE','EMAIL') NOT NULL,
    ip_address VARCHAR(45) NULL,
    device_id BIGINT UNSIGNED NULL,
    attempt_type ENUM('OTP_REQUEST','OTP_VERIFY','LOGIN') NOT NULL,
    status ENUM('SUCCESS','FAILED','BLOCKED') NOT NULL,
    failure_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (device_id) REFERENCES customer_devices(id) ON DELETE SET NULL,
    INDEX idx_login_attempt_identifier (login_identifier),
    INDEX idx_login_attempt_customer (customer_account_id),
    INDEX idx_login_attempt_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_login_attempts');
    }
};
