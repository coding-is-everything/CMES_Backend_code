<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            StateSeeder::class,
            DistrictSeeder::class,
            ProjectStatusSeeder::class,
            ProjectTypeSeeder::class,
            RenewalTypeSeeder::class,
            RenewalStatusSeeder::class,
            DocumentCategorySeeder::class,
            DocumentTypeSeeder::class,
            NotificationCategorySeeder::class,
            NewsCategorySeeder::class,
            BlogCategorySeeder::class,
            CommunityCategorySeeder::class,
            ProjectDynamicFieldSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            AppSettingSeeder::class,
            LegalDocumentSeeder::class,
            NotificationTemplateSeeder::class,
            SupportCategorySeeder::class,
            FaqCategorySeeder::class,
        ]);
    }
}
