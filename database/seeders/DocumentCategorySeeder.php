<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Seed the document category master data.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'LEASE',
                'name' => 'Lease Documents',
                'description' => 'Lease and lease-related documents',
                'sort_order' => 1,
            ],
            [
                'code' => 'APPROVAL',
                'name' => 'Approval Documents',
                'description' => 'Approval/sanction documents',
                'sort_order' => 2,
            ],
            [
                'code' => 'COMPLIANCE',
                'name' => 'Compliance Documents',
                'description' => 'Compliance and regulatory documents',
                'sort_order' => 3,
            ],
            [
                'code' => 'ENVIRONMENT',
                'name' => 'Environmental',
                'description' => 'Environmental clearance and related documents',
                'sort_order' => 4,
            ],
            [
                'code' => 'IDENTITY',
                'name' => 'Identity',
                'description' => 'Identity/ownership documents',
                'sort_order' => 5,
            ],
            [
                'code' => 'TAX',
                'name' => 'Tax',
                'description' => 'Tax and statutory documents',
                'sort_order' => 6,
            ],
            [
                'code' => 'OTHER',
                'name' => 'Other',
                'description' => 'Other project documents',
                'sort_order' => 99,
            ],
        ];

        $now = now();

        $rows = array_map(fn (array $category) => array_merge($category, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $categories);

        DB::table('document_categories')->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'sort_order', 'is_active', 'updated_at']
        );
    }
}
