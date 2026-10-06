<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/admin/auth/login';

    private const PASSWORD = 'Secret@12345';

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'ADM' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Test Admin',
            'email'         => "admin{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = $this->makeAdmin(['email' => 'super@example.com']);

        $response = $this->postJson(self::URL, [
            'email'    => 'super@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admin login successful.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.admin.id', $admin->id)
            ->assertJsonPath('data.admin.email', 'super@example.com')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'admin' => ['id', 'admin_code', 'full_name', 'email', 'mobile_number', 'status', 'last_login_at', 'roles'],
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'access_token_expires_at',
                    'refresh_token_expires_at',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.access_token'));
        $this->assertNotEmpty($response->json('data.refresh_token'));
        $this->assertArrayNotHasKey('password_hash', $response->json('data.admin'));
    }

    public function test_login_returns_admin_roles(): void
    {
        $admin = $this->makeAdmin(['email' => 'roles@example.com']);
        $role  = Role::create([
            'role_name' => 'Super Admin',
            'role_code' => 'SUPER_ADMIN',
        ]);
        DB::table('admin_user_roles')->insert([
            'admin_user_id' => $admin->id,
            'role_id'       => $role->id,
        ]);

        $this->postJson(self::URL, [
            'email'    => 'roles@example.com',
            'password' => self::PASSWORD,
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.admin.roles')
            ->assertJsonPath('data.admin.roles.0.role_code', 'SUPER_ADMIN')
            ->assertJsonPath('data.admin.roles.0.role_name', 'Super Admin');
    }

    public function test_login_updates_last_login_and_creates_token_with_admin_ability(): void
    {
        $admin = $this->makeAdmin(['email' => 'token@example.com']);

        $this->postJson(self::URL, [
            'email'       => 'token@example.com',
            'password'    => self::PASSWORD,
            'device_name' => 'chrome-laptop',
        ])->assertOk();

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);

        $access  = $admin->tokens()->where('name', 'like', 'admin-access:chrome-laptop:%')->first();
        $refresh = $admin->tokens()->where('name', 'like', 'admin-refresh:chrome-laptop:%')->first();

        $this->assertNotNull($access);
        $this->assertNotNull($refresh);
        $this->assertSame(AdminUser::class, $access->tokenable_type);
        $this->assertTrue($access->can('admin:access'));
        $this->assertFalse($access->can('admin:refresh'));
        $this->assertTrue($refresh->can('admin:refresh'));
        $this->assertFalse($refresh->can('admin:access'));
    }

    public function test_default_token_name_is_admin_web_when_no_device_name(): void
    {
        $admin = $this->makeAdmin(['email' => 'default@example.com']);

        $this->postJson(self::URL, [
            'email'    => 'default@example.com',
            'password' => self::PASSWORD,
        ])->assertOk();

        $this->assertSame(1, $admin->tokens()->where('name', 'like', 'admin-access:admin-web:%')->count());
        $this->assertSame(1, $admin->tokens()->where('name', 'like', 'admin-refresh:admin-web:%')->count());
    }

    public function test_same_device_login_replaces_previous_token(): void
    {
        $admin = $this->makeAdmin(['email' => 'device@example.com']);
        $payload = [
            'email'       => 'device@example.com',
            'password'    => self::PASSWORD,
            'device_name' => 'firefox',
        ];

        $this->postJson(self::URL, $payload)->assertOk();
        $this->postJson(self::URL, $payload)->assertOk();

        // One access + one refresh token; the first pair was replaced.
        $this->assertSame(2, $admin->tokens()->count());
        $this->assertSame(1, $admin->tokens()->where('name', 'like', 'admin-access:firefox:%')->count());
        $this->assertSame(1, $admin->tokens()->where('name', 'like', 'admin-refresh:firefox:%')->count());
    }

    public function test_different_devices_keep_separate_tokens(): void
    {
        $admin = $this->makeAdmin(['email' => 'multi@example.com']);

        foreach (['firefox', 'chrome'] as $device) {
            $this->postJson(self::URL, [
                'email'       => 'multi@example.com',
                'password'    => self::PASSWORD,
                'device_name' => $device,
            ])->assertOk();
        }

        // Two devices x (access + refresh).
        $this->assertSame(4, $admin->tokens()->count());
    }

    public function test_email_is_case_insensitive_and_trimmed(): void
    {
        $this->makeAdmin(['email' => 'case@example.com']);

        $this->postJson(self::URL, [
            'email'    => '  CASE@Example.COM ',
            'password' => self::PASSWORD,
        ])->assertOk();
    }

    public function test_issued_token_authenticates_against_sanctum(): void
    {
        $this->makeAdmin(['email' => 'auth@example.com']);

        $token = $this->postJson(self::URL, [
            'email'    => 'auth@example.com',
            'password' => self::PASSWORD,
        ])->json('data.access_token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'auth@example.com');
    }

    public function test_wrong_password_is_rejected_with_generic_message(): void
    {
        $this->makeAdmin(['email' => 'wrong@example.com']);

        $this->postJson(self::URL, [
            'email'    => 'wrong@example.com',
            'password' => 'WrongPassword1',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Invalid email or password.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unknown_email_returns_same_message_as_wrong_password(): void
    {
        $this->postJson(self::URL, [
            'email'    => 'nobody@example.com',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Invalid email or password.');
    }

    public function test_locked_admin_cannot_login(): void
    {
        $this->makeAdmin(['email' => 'locked@example.com', 'status' => 'LOCKED']);

        $this->postJson(self::URL, [
            'email'    => 'locked@example.com',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Your administrator account is locked.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_admin_cannot_login(): void
    {
        $this->makeAdmin(['email' => 'inactive@example.com', 'status' => 'INACTIVE']);

        $this->postJson(self::URL, [
            'email'    => 'inactive@example.com',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Your administrator account is inactive.');
    }

    public function test_soft_deleted_admin_cannot_login(): void
    {
        $admin = $this->makeAdmin(['email' => 'deleted@example.com']);
        $admin->delete();

        $this->postJson(self::URL, [
            'email'    => 'deleted@example.com',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Invalid email or password.');
    }

    public function test_email_and_password_are_required(): void
    {
        $this->postJson(self::URL, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password'])
            ->assertJsonPath('errors.email.0', 'Email address is required.')
            ->assertJsonPath('errors.password.0', 'Password is required.');
    }

    public function test_email_must_be_valid_format(): void
    {
        $this->postJson(self::URL, [
            'email'    => 'not-an-email',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Please enter a valid email address.');
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $this->postJson(self::URL, [
            'email'    => 'short@example.com',
            'password' => 'short',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password must be at least 8 characters.');
    }

    public function test_device_name_cannot_exceed_100_characters(): void
    {
        $this->postJson(self::URL, [
            'email'       => 'long@example.com',
            'password'    => self::PASSWORD,
            'device_name' => str_repeat('a', 101),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['device_name']);
    }

    public function test_account_is_throttled_after_five_failed_attempts(): void
    {
        $this->makeAdmin(['email' => 'brute@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::URL, [
                'email'    => 'brute@example.com',
                'password' => 'WrongPassword1',
            ])->assertStatus(422)
                ->assertJsonPath('errors.email.0', 'Invalid email or password.');
        }

        // Even the correct password is blocked while throttled.
        $response = $this->postJson(self::URL, [
            'email'    => 'brute@example.com',
            'password' => self::PASSWORD,
        ])->assertStatus(422);

        $this->assertStringContainsString(
            'Too many login attempts',
            $response->json('errors.email.0')
        );
    }

    public function test_successful_login_clears_failed_attempt_counter(): void
    {
        $this->makeAdmin(['email' => 'reset@example.com']);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson(self::URL, [
                'email'    => 'reset@example.com',
                'password' => 'WrongPassword1',
            ])->assertStatus(422);
        }

        $this->postJson(self::URL, [
            'email'    => 'reset@example.com',
            'password' => self::PASSWORD,
        ])->assertOk();

        // Counter was cleared, so 4 more failures are still plain credential errors.
        for ($i = 0; $i < 4; $i++) {
            $this->postJson(self::URL, [
                'email'    => 'reset@example.com',
                'password' => 'WrongPassword1',
            ])->assertJsonPath('errors.email.0', 'Invalid email or password.');
        }
    }

    public function test_route_level_throttle_returns_429_after_ten_requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(self::URL, [
                'email'    => 'flood@example.com',
                'password' => self::PASSWORD,
            ]);
        }

        $this->postJson(self::URL, [
            'email'    => 'flood@example.com',
            'password' => self::PASSWORD,
        ])->assertStatus(429);
    }
}
