<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunityCategorySeeder extends Seeder
{
    /**
     * Seed the community category master data.
     *
     * This seeder is idempotent and can safely be executed multiple times.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'General Discussion',
                'slug'          => 'general-discussion',
                'is_active'     => true,
            ],
            [
                'category_name' => 'Mining Operations',
                'slug'          => 'mining-operations',
                'is_active'     => true,
            ],
            [
                'category_name' => 'Compliance & Regulation',
                'slug'          => 'compliance-regulation',
                'is_active'     => true,
            ],
            [
                'category_name' => 'Environment',
                'slug'          => 'environment',
                'is_active'     => true,
            ],
            [
                'category_name' => 'Technology',
                'slug'          => 'technology',
                'is_active'     => true,
            ],
            [
                'category_name' => 'Help & Support',
                'slug'          => 'help-support',
                'is_active'     => true,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('community_categories')->updateOrInsert(
                [
                    'slug' => $category['slug'],
                ],
                [
                    'category_name' => $category['category_name'],
                    'is_active'     => $category['is_active'],
                ]
            );
        }
    }
}
