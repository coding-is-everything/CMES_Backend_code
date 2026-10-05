<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSettingSeeder extends Seeder
{
    /**
     * Seed the application settings master data.
     *
     * This seeder is idempotent and can safely be executed multiple times.
     */
    public function run(): void
    {
        $settings = [
            [
                'key_name'    => 'app_name',
                'value_json'  => json_encode('CMES'),
                'environment' => 'production',
                'is_public'   => true,
            ],
            [
                'key_name'    => 'default_locale',
                'value_json'  => json_encode('en'),
                'environment' => 'production',
                'is_public'   => true,
            ],
            [
                'key_name'    => 'default_timezone',
                'value_json'  => json_encode('Asia/Kolkata'),
                'environment' => 'production',
                'is_public'   => true,
            ],
            [
                'key_name'    => 'date_format',
                'value_json'  => json_encode('DD-MM-YYYY'),
                'environment' => 'production',
                'is_public'   => true,
            ],
            [
                'key_name'    => 'maintenance_mode',
                'value_json'  => json_encode(false),
                'environment' => 'production',
                'is_public'   => false,
            ],
            [
                'key_name'    => 'support_email',
                'value_json'  => json_encode(''),
                'environment' => 'production',
                'is_public'   => false,
            ],
            [
                'key_name'    => 'support_phone',
                'value_json'  => json_encode(''),
                'environment' => 'production',
                'is_public'   => false,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('app_settings')->updateOrInsert(
                [
                    'key_name'    => $setting['key_name'],
                    'environment' => $setting['environment'],
                ],
                [
                    'value_json' => $setting['value_json'],
                    'is_public'  => $setting['is_public'],
                ]
            );
        }
    }
}
