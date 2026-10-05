<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogCategorySeeder extends Seeder
{
    /**
     * Seed the blog category master data.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Mining Guides',
                'slug' => 'mining-guides',
            ],
            [
                'category_name' => 'Compliance',
                'slug' => 'compliance',
            ],
            [
                'category_name' => 'Industry Insights',
                'slug' => 'industry-insights',
            ],
            [
                'category_name' => 'How To',
                'slug' => 'how-to',
            ],
            [
                'category_name' => 'Technology',
                'slug' => 'technology',
            ],
            [
                'category_name' => 'General',
                'slug' => 'general',
            ],
        ];

        $rows = array_map(fn (array $category) => array_merge($category, [
            'status' => 'ACTIVE',
        ]), $categories);

        DB::table('blog_categories')->upsert(
            $rows,
            ['slug'],
            ['category_name', 'status']
        );
    }
}
