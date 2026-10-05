<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsCategorySeeder extends Seeder
{
    /**
     * Seed the news category master data.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Government Updates',
                'slug' => 'government-updates',
            ],
            [
                'category_name' => 'Mining Industry',
                'slug' => 'mining-industry',
            ],
            [
                'category_name' => 'Regulatory',
                'slug' => 'regulatory',
            ],
            [
                'category_name' => 'Environment',
                'slug' => 'environment',
            ],
            [
                'category_name' => 'Technology',
                'slug' => 'technology',
            ],
            [
                'category_name' => 'Announcements',
                'slug' => 'announcements',
            ],
        ];

        $rows = array_map(fn (array $category) => array_merge($category, [
            'status' => 'ACTIVE',
        ]), $categories);

        DB::table('news_categories')->upsert(
            $rows,
            ['slug'],
            ['category_name', 'status']
        );
    }
}
