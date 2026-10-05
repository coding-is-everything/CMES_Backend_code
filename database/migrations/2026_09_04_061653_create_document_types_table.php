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
CREATE TABLE document_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_type_code VARCHAR(50) NOT NULL UNIQUE,
    document_type_name VARCHAR(150) NOT NULL UNIQUE,
    category ENUM('LEASE','MINING','COMPLIANCE','ENVIRONMENT','GOVERNMENT_APPROVAL','RENEWAL','OTHER') NOT NULL,
    expiry_required BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
