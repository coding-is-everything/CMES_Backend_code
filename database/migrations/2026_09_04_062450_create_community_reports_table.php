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
CREATE TABLE community_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id BIGINT UNSIGNED NULL,
    reply_id BIGINT UNSIGNED NULL,
    reported_by_customer_id BIGINT UNSIGNED NOT NULL,
    reason ENUM('SPAM','OFFENSIVE','MISLEADING','HARASSMENT','IRRELEVANT','OTHER') NOT NULL,
    description TEXT NULL,
    status ENUM('PENDING','REVIEWED','DISMISSED','ACTION_TAKEN') NOT NULL DEFAULT 'PENDING',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_report_discussion FOREIGN KEY (discussion_id)
        REFERENCES community_discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_reply FOREIGN KEY (reply_id)
        REFERENCES community_replies(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_customer FOREIGN KEY (reported_by_customer_id)
        REFERENCES customer_accounts(id) ON DELETE RESTRICT,
    CONSTRAINT fk_report_reviewed_by FOREIGN KEY (reviewed_by)
        REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_report_status (status)
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_reports');
    }
};
