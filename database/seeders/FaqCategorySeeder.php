<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqCategorySeeder extends Seeder
{
    /**
     * Seed FAQ category master data.
     *
     * This seeder is idempotent and can safely be executed multiple times.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'category_name' => 'Account & Login',
                'slug'          => 'account',
                'display_order' => 1,
                'status'        => 'ACTIVE',
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_name' => 'Projects',
                'slug'          => 'project',
                'display_order' => 2,
                'status'        => 'ACTIVE',
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_name' => 'Documents',
                'slug'          => 'documents',
                'display_order' => 3,
                'status'        => 'ACTIVE',
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_name' => 'Renewals',
                'slug'          => 'renewals',
                'display_order' => 4,
                'status'        => 'ACTIVE',
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_name' => 'Mobile Application',
                'slug'          => 'app',
                'display_order' => 5,
                'status'        => 'ACTIVE',
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_name' => 'General',
                'slug'          => 'general',
                'display_order' => 99,
                'status'        => 'ACTIVE',
                'created_by'    => null,
                'updated_by'    => null,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('faq_categories')->updateOrInsert(
                [
                    'slug' => $category['slug'],
                ],
                [
                    'category_name' => $category['category_name'],
                    'display_order' => $category['display_order'],
                    'status'        => $category['status'],
                    'created_by'    => $category['created_by'],
                    'updated_by'    => $category['updated_by'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );
        }
    }
}
