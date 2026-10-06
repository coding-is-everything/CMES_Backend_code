<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Only the SHA-256 hash of a reset token is stored; the raw token
     * exists solely in the e-mail sent to the administrator.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE admin_password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_admin_password_reset_token_hash (token_hash),
    INDEX idx_admin_password_reset_user (admin_user_id),
    INDEX idx_admin_password_reset_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_password_resets');
    }
};
