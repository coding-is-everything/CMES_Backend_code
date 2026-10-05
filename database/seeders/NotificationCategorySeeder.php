<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationCategorySeeder extends Seeder
{
    /**
     * Seed the notification category master data.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'RENEWAL',
                'name' => 'Renewal',
                'description' => 'Renewal reminders and status',
                'sort_order' => 1,
            ],
            [
                'code' => 'DOCUMENT',
                'name' => 'Document',
                'description' => 'Document availability/expiry',
                'sort_order' => 2,
            ],
            [
                'code' => 'COMPLIANCE',
                'name' => 'Compliance',
                'description' => 'Compliance alerts',
                'sort_order' => 3,
            ],
            [
                'code' => 'PROJECT_UPDATE',
                'name' => 'Project Update',
                'description' => 'Project status/update',
                'sort_order' => 4,
            ],
            [
                'code' => 'SYSTEM',
                'name' => 'System',
                'description' => 'System/service notifications',
                'sort_order' => 5,
            ],
            [
                'code' => 'NEWS',
                'name' => 'News',
                'description' => 'News notifications',
                'sort_order' => 6,
            ],
            [
                'code' => 'BLOG',
                'name' => 'Blog',
                'description' => 'Blog notifications',
                'sort_order' => 7,
            ],
            [
                'code' => 'COMMUNITY',
                'name' => 'Community',
                'description' => 'Community notifications',
                'sort_order' => 8,
            ],
            [
                'code' => 'OTHER',
                'name' => 'Other',
                'description' => 'Other notifications',
                'sort_order' => 99,
            ],
        ];

        $now = now();

        $rows = array_map(fn (array $category) => array_merge($category, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $categories);

        DB::table('notification_categories')->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'sort_order', 'is_active', 'updated_at']
        );
    }
}
