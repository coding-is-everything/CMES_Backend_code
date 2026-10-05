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
CREATE TABLE admin_user_roles (
    admin_user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (admin_user_id, role_id),
    CONSTRAINT fk_admin_role_user FOREIGN KEY (admin_user_id)
        REFERENCES admin_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_role_role FOREIGN KEY (role_id)
        REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_user_roles');
    }
};
