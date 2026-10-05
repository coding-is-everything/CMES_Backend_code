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
CREATE TABLE support_ticket_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    uploaded_by_customer_id BIGINT UNSIGNED NULL,
    uploaded_by_admin_id BIGINT UNSIGNED NULL,
    original_file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NULL,
    file_size BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by_customer_id) REFERENCES customer_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_ticket_attachment_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
    }
};
