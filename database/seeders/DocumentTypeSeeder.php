<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Seed the document type master data.
     */
    public function run(): void
    {
        $types = [
            [
                'document_type_code' => 'LEASE_DEED',
                'document_type_name' => 'Lease Deed',
                'category' => 'LEASE',
                'expiry_required' => true,
            ],
            [
                'document_type_code' => 'APPROVAL_LETTER',
                'document_type_name' => 'Approval Letter',
                'category' => 'GOVERNMENT_APPROVAL',
                'expiry_required' => false,
            ],
            [
                'document_type_code' => 'ENVIRONMENT_CLEARANCE',
                'document_type_name' => 'Environmental Clearance',
                'category' => 'ENVIRONMENT',
                'expiry_required' => true,
            ],
            [
                'document_type_code' => 'CONSENT_ORDER',
                'document_type_name' => 'Consent Order',
                'category' => 'COMPLIANCE',
                'expiry_required' => true,
            ],
            [
                'document_type_code' => 'ROYALTY_DOCUMENT',
                'document_type_name' => 'Royalty Document',
                'category' => 'MINING',
                'expiry_required' => false,
            ],
            [
                'document_type_code' => 'INSPECTION_REPORT',
                'document_type_name' => 'Inspection Report',
                'category' => 'COMPLIANCE',
                'expiry_required' => false,
            ],
            [
                'document_type_code' => 'OTHER',
                'document_type_name' => 'Other',
                'category' => 'OTHER',
                'expiry_required' => false,
            ],
        ];

        $rows = array_map(fn (array $type) => array_merge($type, [
            'status' => 'ACTIVE',
        ]), $types);

        DB::table('document_types')->upsert(
            $rows,
            ['document_type_code'],
            ['document_type_name', 'category', 'expiry_required', 'status']
        );
    }
}
