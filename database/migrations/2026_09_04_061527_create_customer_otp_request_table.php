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
        CREATE TABLE customer_otp_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_account_id BIGINT UNSIGNED NULL,
            verification_type ENUM('MOBILE', 'EMAIL') NOT NULL,
            destination VARCHAR(255) NOT NULL,
            otp_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified_at DATETIME NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM ('PENDING', 'VERIFIED', 'EXPIRED', 'FAILED', 'BLOCKED') NOT NULL DEFAULT 'PENDING',
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_otp_customer FOREIGN KEY (customer_account_id)
            REFERENCES customer_accounts(id) ON DELETE SET NULL,
            INDEX idx_otp_destination (destination),
            INDEX idx_otp_status (status),
            INDEX idx_otp_expires (expires_at)
        ) ENGINE=InnoDB;
        SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_otp_request');
    }
};
