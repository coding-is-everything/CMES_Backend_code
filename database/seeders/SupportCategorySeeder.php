<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportCategorySeeder extends Seeder
{
    /**
     * Seed support category master data.
     *
     * This seeder is idempotent and can safely be executed multiple times.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'category_code' => 'LOGIN',
                'category_name' => 'Login & Account',
                'description'   => 'Login, registration and account issues',
                'sort_order'    => 1,
                'is_active'     => true,
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_code' => 'PROJECT',
                'category_name' => 'Project',
                'description'   => 'Project-related support',
                'sort_order'    => 2,
                'is_active'     => true,
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_code' => 'DOCUMENT',
                'category_name' => 'Documents',
                'description'   => 'Document access/download issues',
                'sort_order'    => 3,
                'is_active'     => true,
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_code' => 'RENEWAL',
                'category_name' => 'Renewal',
                'description'   => 'Renewal-related support',
                'sort_order'    => 4,
                'is_active'     => true,
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_code' => 'TECHNICAL',
                'category_name' => 'Technical',
                'description'   => 'Technical/app issues',
                'sort_order'    => 5,
                'is_active'     => true,
                'created_by'    => null,
                'updated_by'    => null,
            ],
            [
                'category_code' => 'OTHER',
                'category_name' => 'Other',
                'description'   => 'Other support requests',
                'sort_order'    => 6,
                'is_active'     => true,
                'created_by'    => null,
                'updated_by'    => null,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('support_categories')->updateOrInsert(
                [
                    'category_code' => $category['category_code'],
                ],
                [
                    'category_name' => $category['category_name'],
                    'description'   => $category['description'],
                    'sort_order'    => $category['sort_order'],
                    'is_active'     => $category['is_active'],
                    'created_by'    => $category['created_by'],
                    'updated_by'    => $category['updated_by'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );
        }
    }
}
