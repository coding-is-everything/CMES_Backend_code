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
        CREATE TABLE districts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            state_id BIGINT UNSIGNED NOT NULL,
            district_name VARCHAR(100) NOT NULL,
            status BOOLEAN NOT NULL DEFAULT TRUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_district_state FOREIGN KEY (state_id)
            REFERENCES states(id) ON DELETE RESTRICT,
            UNIQUE KEY uq_state_district (state_id, district_name),
            INDEX idx_district_state (state_id)
        ) ENGINE=InnoDB;
        SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
