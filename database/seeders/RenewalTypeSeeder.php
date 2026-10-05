<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RenewalTypeSeeder extends Seeder
{
    /**
     * Seed the renewal type master data.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'LEASE_RENEWAL',
                'name' => 'Lease Renewal',
                'description' => 'Renewal of mining/mineral lease',
            ],
            [
                'code' => 'PERMIT_RENEWAL',
                'name' => 'Permit Renewal',
                'description' => 'Renewal of permit',
            ],
            [
                'code' => 'NOC_RENEWAL',
                'name' => 'NOC Renewal',
                'description' => 'Renewal of no-objection certificate',
            ],
            [
                'code' => 'OTHER',
                'name' => 'Other',
                'description' => 'Other renewal type',
            ],
        ];

        $now = now();

        $rows = array_map(fn (array $type) => array_merge($type, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $types);

        DB::table('renewal_types')->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'is_active', 'updated_at']
        );
    }
}
