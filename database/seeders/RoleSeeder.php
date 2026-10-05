<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Seed the role master data.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'Super Administrator',
                'role_code' => 'SUPER_ADMIN',
                'description' => 'Full system administration',
            ],
            [
                'role_name' => 'Administrator',
                'role_code' => 'ADMIN',
                'description' => 'Administrative access',
            ],
            [
                'role_name' => 'Operations Manager',
                'role_code' => 'OPERATIONS_MANAGER',
                'description' => 'Operational/project management',
            ],
            [
                'role_name' => 'Content Manager',
                'role_code' => 'CONTENT_MANAGER',
                'description' => 'News/blog/content management',
            ],
            [
                'role_name' => 'Support Manager',
                'role_code' => 'SUPPORT_MANAGER',
                'description' => 'Customer support management',
            ],
            [
                'role_name' => 'Compliance Officer',
                'role_code' => 'COMPLIANCE_OFFICER',
                'description' => 'Compliance and document review',
            ],
            [
                'role_name' => 'Viewer',
                'role_code' => 'VIEWER',
                'description' => 'Read-only administrative access',
            ],
        ];

        DB::table('roles')->upsert(
            $roles,
            ['role_code'],
            ['role_name', 'description']
        );
    }
}
