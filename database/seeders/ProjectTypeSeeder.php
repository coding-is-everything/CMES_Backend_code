<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectTypeSeeder extends Seeder
{
    /**
     * Seed the project's type master data.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'MINE',
                'name' => 'Mine',
                'description' => 'Mining project',
            ],
            [
                'code' => 'QUARRY',
                'name' => 'Quarry',
                'description' => 'Quarry operation',
            ],
            [
                'code' => 'MINERAL_LEASE',
                'name' => 'Mineral Lease',
                'description' => 'Mineral lease project',
            ],
            [
                'code' => 'MINOR_MINERAL',
                'name' => 'Minor Mineral',
                'description' => 'Minor mineral project',
            ],
            [
                'code' => 'OTHER',
                'name' => 'Other',
                'description' => 'Other approved project type',
            ],
        ];

        $now = now();

        $rows = array_map(fn (array $type) => array_merge($type, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $types);

        DB::table('project_types')->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'is_active', 'updated_at']
        );
    }
}
