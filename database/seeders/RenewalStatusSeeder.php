<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RenewalStatusSeeder extends Seeder
{
    /**
     * Seed the renewal status master data.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'DRAFT',
                'name' => 'Draft',
                'description' => 'Renewal draft',
                'sort_order' => 1,
            ],
            [
                'code' => 'SUBMITTED',
                'name' => 'Submitted',
                'description' => 'Renewal submitted',
                'sort_order' => 2,
            ],
            [
                'code' => 'UNDER_REVIEW',
                'name' => 'Under Review',
                'description' => 'Renewal under review',
                'sort_order' => 3,
            ],
            [
                'code' => 'APPROVED',
                'name' => 'Approved',
                'description' => 'Renewal approved',
                'sort_order' => 4,
            ],
            [
                'code' => 'REJECTED',
                'name' => 'Rejected',
                'description' => 'Renewal rejected',
                'sort_order' => 5,
            ],
            [
                'code' => 'EXPIRED',
                'name' => 'Expired',
                'description' => 'Renewal expired',
                'sort_order' => 6,
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'description' => 'Renewal cancelled',
                'sort_order' => 7,
            ],
        ];

        $now = now();

        $rows = array_map(fn (array $status) => array_merge($status, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $statuses);

        DB::table('renewal_statuses')->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'sort_order', 'is_active', 'updated_at']
        );
    }
}
