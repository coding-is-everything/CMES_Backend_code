<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStatusSeeder extends Seeder
{
    /**
     * Seed the project's status master data.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'DRAFT',
                'name' => 'Draft',
                'description' => 'Project record has been created but not yet submitted.',
                'sort_order' => 1,
            ],
            [
                'code' => 'SUBMITTED',
                'name' => 'Submitted',
                'description' => 'Project has been submitted and is awaiting review.',
                'sort_order' => 2,
            ],
            [
                'code' => 'UNDER_REVIEW',
                'name' => 'Under Review',
                'description' => 'Project is currently under administrative or compliance review.',
                'sort_order' => 3,
            ],
            [
                'code' => 'APPROVED',
                'name' => 'Approved',
                'description' => 'Project has been approved by the authorized authority.',
                'sort_order' => 4,
            ],
            [
                'code' => 'ACTIVE',
                'name' => 'Active',
                'description' => 'Project is currently active and operational.',
                'sort_order' => 5,
            ],
            [
                'code' => 'SUSPENDED',
                'name' => 'Suspended',
                'description' => 'Project has been temporarily suspended.',
                'sort_order' => 6,
            ],
            [
                'code' => 'EXPIRED',
                'name' => 'Expired',
                'description' => 'Project authorization, lease, or applicable validity period has expired.',
                'sort_order' => 7,
            ],
            [
                'code' => 'CLOSED',
                'name' => 'Closed',
                'description' => 'Project has been permanently closed.',
                'sort_order' => 8,
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'description' => 'Project has been cancelled and is no longer active.',
                'sort_order' => 9,
            ],
        ];

        $now = now();

        $rows = array_map(fn (array $status) => array_merge($status, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $statuses);

        DB::table('project_statuses')->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'sort_order', 'is_active', 'updated_at']
        );
    }
}
