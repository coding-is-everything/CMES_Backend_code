<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectDynamicFieldSeeder extends Seeder
{
    /**
     * Seed the project dynamic field master data.
     */
    public function run(): void
    {
        $fields = [
            [
                'code' => 'MINERAL_TYPE',
                'label' => 'Mineral Type',
                'field_type' => 'SELECT',
                'group_name' => 'Mining',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'MINING_METHOD',
                'label' => 'Mining Method',
                'field_type' => 'SELECT',
                'group_name' => 'Mining',
                'is_required' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'LEASE_AREA',
                'label' => 'Lease Area',
                'field_type' => 'NUMBER',
                'group_name' => 'Lease',
                'is_required' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'LEASE_AREA_UNIT',
                'label' => 'Lease Area Unit',
                'field_type' => 'SELECT',
                'group_name' => 'Lease',
                'is_required' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'PRODUCTION_CAPACITY',
                'label' => 'Production Capacity',
                'field_type' => 'NUMBER',
                'group_name' => 'Production',
                'is_required' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'PRODUCTION_CAPACITY_UNIT',
                'label' => 'Production Capacity Unit',
                'field_type' => 'SELECT',
                'group_name' => 'Production',
                'is_required' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'MINING_LEASE_NUMBER',
                'label' => 'Mining Lease Number',
                'field_type' => 'TEXT',
                'group_name' => 'Lease',
                'is_required' => false,
                'sort_order' => 7,
            ],
            [
                'code' => 'ENVIRONMENTAL_CLEARANCE_NUMBER',
                'label' => 'Environmental Clearance Number',
                'field_type' => 'TEXT',
                'group_name' => 'Compliance',
                'is_required' => false,
                'sort_order' => 8,
            ],
        ];

        $now = now();

        $rows = array_map(fn (array $field) => array_merge($field, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $fields);

        DB::table('project_dynamic_fields')->upsert(
            $rows,
            ['code'],
            ['label', 'field_type', 'group_name', 'is_required', 'sort_order', 'is_active', 'updated_at']
        );
    }
}
