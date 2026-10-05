<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the permission master data.
     *
     * This seeder is idempotent and can safely be executed multiple times.
     */
    public function run(): void
    {
        $permissions = [
            [
                'permission_code' => 'users.view',
                'permission_name' => 'View Users',
                'module_name'     => 'Users',
            ],
            [
                'permission_code' => 'users.manage',
                'permission_name' => 'Manage Users',
                'module_name'     => 'Users',
            ],
            [
                'permission_code' => 'projects.view',
                'permission_name' => 'View Projects',
                'module_name'     => 'Projects',
            ],
            [
                'permission_code' => 'projects.manage',
                'permission_name' => 'Manage Projects',
                'module_name'     => 'Projects',
            ],
            [
                'permission_code' => 'projects.approve',
                'permission_name' => 'Approve Projects',
                'module_name'     => 'Projects',
            ],
            [
                'permission_code' => 'leases.view',
                'permission_name' => 'View Leases',
                'module_name'     => 'Leases',
            ],
            [
                'permission_code' => 'leases.manage',
                'permission_name' => 'Manage Leases',
                'module_name'     => 'Leases',
            ],
            [
                'permission_code' => 'renewals.view',
                'permission_name' => 'View Renewals',
                'module_name'     => 'Renewals',
            ],
            [
                'permission_code' => 'renewals.manage',
                'permission_name' => 'Manage Renewals',
                'module_name'     => 'Renewals',
            ],
            [
                'permission_code' => 'documents.view',
                'permission_name' => 'View Documents',
                'module_name'     => 'Documents',
            ],
            [
                'permission_code' => 'documents.manage',
                'permission_name' => 'Manage Documents',
                'module_name'     => 'Documents',
            ],
            [
                'permission_code' => 'notifications.manage',
                'permission_name' => 'Manage Notifications',
                'module_name'     => 'Notifications',
            ],
            [
                'permission_code' => 'news.manage',
                'permission_name' => 'Manage News',
                'module_name'     => 'News',
            ],
            [
                'permission_code' => 'blogs.manage',
                'permission_name' => 'Manage Blogs',
                'module_name'     => 'Blogs',
            ],
            [
                'permission_code' => 'community.manage',
                'permission_name' => 'Manage Community',
                'module_name'     => 'Community',
            ],
            [
                'permission_code' => 'support.view',
                'permission_name' => 'View Support',
                'module_name'     => 'Support',
            ],
            [
                'permission_code' => 'support.manage',
                'permission_name' => 'Manage Support',
                'module_name'     => 'Support',
            ],
            [
                'permission_code' => 'reports.view',
                'permission_name' => 'View Reports',
                'module_name'     => 'Reports',
            ],
            [
                'permission_code' => 'settings.manage',
                'permission_name' => 'Manage Settings',
                'module_name'     => 'Settings',
            ],
            [
                'permission_code' => 'masters.manage',
                'permission_name' => 'Manage Master Data',
                'module_name'     => 'Master Data',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                [
                    'permission_code' => $permission['permission_code'],
                ],
                [
                    'permission_name' => $permission['permission_name'],
                    'module_name'     => $permission['module_name'],
                ]
            );
        }
    }
}
