<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Seed notification template master data.
     *
     * This seeder is idempotent and can safely be executed multiple times.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $templates = [
            [
                'template_code'     => 'RENEWAL_REMINDER',
                'template_name'     => 'Renewal Reminder',
                'notification_type' => 'RENEWAL',
                'title_template'    => 'Renewal due reminder',
                'message_template'  => 'Your renewal is approaching. Please review the renewal details.',
                'is_active'         => true,
            ],
            [
                'template_code'     => 'DOCUMENT_EXPIRY',
                'template_name'     => 'Document Expiry Alert',
                'notification_type' => 'DOCUMENT',
                'title_template'    => 'Document expiry alert',
                'message_template'  => 'A project document is approaching its expiry date.',
                'is_active'         => true,
            ],
            [
                'template_code'     => 'PROJECT_UPDATE',
                'template_name'     => 'Project Update',
                'notification_type' => 'PROJECT_UPDATE',
                'title_template'    => 'Project status updated',
                'message_template'  => 'There has been an update to your project.',
                'is_active'         => true,
            ],
            [
                'template_code'     => 'SYSTEM_MAINTENANCE',
                'template_name'     => 'System Maintenance',
                'notification_type' => 'SYSTEM',
                'title_template'    => 'Scheduled maintenance',
                'message_template'  => 'The CMES service may be temporarily unavailable during scheduled maintenance.',
                'is_active'         => true,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('notification_templates')->updateOrInsert(
                [
                    'template_code' => $template['template_code'],
                ],
                [
                    'template_name'     => $template['template_name'],
                    'notification_type' => $template['notification_type'],
                    'title_template'    => $template['title_template'],
                    'message_template'  => $template['message_template'],
                    'is_active'         => $template['is_active'],
                    'created_at'        => $now,
                ]
            );
        }
    }
}
