<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const REFRESH_URL = '/api/v1/admin/auth/refresh';

    private const URL = '/api/v1/admin/auth/change-password';

    private const OLD_PASSWORD = 'Secret@12345';

    private const NEW_PASSWORD = 'Brand#New9876';

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'CHP' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Change Admin',
            'email'         => "change{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::OLD_PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function loginAs(AdminUser $admin, ?string $deviceName = null, string $password = self::OLD_PASSWORD): array
    {
        $payload = ['email' => $admin->email, 'password' => $password];

        if ($deviceName !== null) {
            $payload['device_name'] = $deviceName;
        }

        $response = $this->postJson(self::LOGIN_URL, $payload)->assertOk();

        return [
            'access_token'  => $response->json('data.access_token'),
            'refresh_token' => $response->json('data.refresh_token'),
        ];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'current_password'      => self::OLD_PASSWORD,
            'password'              => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ], $overrides);
    }

    /**
     * Sanctum caches the resolved user per guard; reset it so each request
     * authenticates from the bearer token again.
     */
    private function changeWith(string $accessToken, array $payload)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($accessToken)->postJson(self::URL, $payload);
    }

    private function tokenRecord(string $plainTextToken): ?PersonalAccessToken
    {
        return PersonalAccessToken::findToken($plainTextToken);
    }

    public function test_admin_can_change_password(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->changeWith($tokens['access_token'], $this->payload())
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Password changed successfully. Other devices have been signed out.',
                'data'    => null,
            ]);

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_admin_can_log_in_with_new_password_and_not_the_old_one(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->changeWith($tokens['access_token'], $this->payload())->assertOk();

        $this->loginAs($admin, null, self::NEW_PASSWORD);

        $this->postJson(self::LOGIN_URL, ['email' => $admin->email, 'password' => self::OLD_PASSWORD])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Invalid email or password.');
    }

    public function test_password_is_stored_hashed(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->changeWith($tokens['access_token'], $this->payload())->assertOk();

        $stored = $admin->fresh()->password_hash;

        $this->assertNotSame(self::NEW_PASSWORD, $stored);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $stored));
    }

    public function test_current_session_stays_signed_in(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->changeWith($tokens['access_token'], $this->payload())->assertOk();

        $this->assertNotNull($this->tokenRecord($tokens['access_token']));
        $this->assertNotNull($this->tokenRecord($tokens['refresh_token']));

        $this->app['auth']->forgetGuards();
        $this->withToken($tokens['access_token'])
            ->getJson('/api/v1/admin/auth/me')
            ->assertOk();

        $this->postJson(self::REFRESH_URL, ['refresh_token' => $tokens['refresh_token']])->assertOk();
    }

    public function test_other_device_sessions_are_revoked(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');
        $third   = $this->loginAs($admin, 'mobile');

        $this->assertSame(6, $admin->tokens()->count());

        $this->changeWith($current['access_token'], $this->payload())->assertOk();

        $this->assertNull($this->tokenRecord($other['access_token']));
        $this->assertNull($this->tokenRecord($other['refresh_token']));
        $this->assertNull($this->tokenRecord($third['access_token']));
        $this->assertNull($this->tokenRecord($third['refresh_token']));

        $this->assertSame(2, $admin->tokens()->count());

        $this->postJson(self::REFRESH_URL, ['refresh_token' => $other['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid or expired refresh token.');
    }

    public function test_other_admins_are_not_affected(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        $tokens      = $this->loginAs($admin);
        $otherTokens = $this->loginAs($other);

        $this->changeWith($tokens['access_token'], $this->payload())->assertOk();

        $this->assertSame(2, $other->tokens()->count());
        $this->assertNotNull($this->tokenRecord($otherTokens['access_token']));
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $other->fresh()->password_hash));
    }

    public function test_outstanding_reset_links_are_voided(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        DB::table('admin_password_resets')->insert([
            'admin_user_id' => $admin->id,
            'token_hash'    => hash('sha256', 'pending-link'),
            'expires_at'    => now()->addHour(),
            'created_at'    => now(),
        ]);

        $this->changeWith($tokens['access_token'], $this->payload())->assertOk();

        $this->assertDatabaseCount('admin_password_resets', 0);

        $this->postJson('/api/v1/admin/auth/reset-password', [
            'email'                 => $admin->email,
            'token'                 => 'pending-link',
            'password'              => 'Hijack#Pass123',
            'password_confirmation' => 'Hijack#Pass123',
        ])->assertStatus(422);
    }

    public function test_failed_login_lockout_is_cleared(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('admin-login:' . $admin->email, 60);
        }

        $this->changeWith($tokens['access_token'], $this->payload())->assertOk();

        $this->loginAs($admin, null, self::NEW_PASSWORD);
    }

    public function test_wrong_current_password_is_rejected_and_nothing_changes(): void
    {
        $admin   = $this->makeAdmin();
        $tokens  = $this->loginAs($admin, 'chrome');
        $another = $this->loginAs($admin, 'firefox');

        $this->changeWith($tokens['access_token'], $this->payload(['current_password' => 'Wrong#Pass123']))
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Current password is incorrect.');

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
        $this->assertNotNull($this->tokenRecord($another['access_token']));
        $this->assertSame(4, $admin->tokens()->count());
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->changeWith($tokens['access_token'], $this->payload([
            'password'              => self::OLD_PASSWORD,
            'password_confirmation' => self::OLD_PASSWORD,
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'New password must be different from the current password.');
    }

    public function test_password_whitespace_is_preserved(): void
    {
        $admin    = $this->makeAdmin();
        $tokens   = $this->loginAs($admin);
        $password = '  spaced pass 1  ';

        $this->changeWith($tokens['access_token'], $this->payload([
            'password'              => $password,
            'password_confirmation' => $password,
        ]))->assertOk();

        $this->assertTrue(Hash::check($password, $admin->fresh()->password_hash));
    }

    public function test_current_password_is_required(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->changeWith($tokens['access_token'], $this->payload(['current_password' => null]))
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Current password is required.');
    }

    public function test_current_password_must_be_a_string(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->changeWith($tokens['access_token'], $this->payload(['current_password' => ['x']]))
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Current password must be a string.');
    }

    public function test_current_password_cannot_exceed_255_characters(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->changeWith($tokens['access_token'], $this->payload(['current_password' => str_repeat('a', 256)]))
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Current password may not exceed 255 characters.');
    }

    public function test_new_password_is_required(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->changeWith($tokens['access_token'], ['current_password' => self::OLD_PASSWORD])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'New password is required.');
    }

    public function test_new_password_must_be_at_least_8_characters(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->changeWith($tokens['access_token'], $this->payload([
            'password'              => 'short12',
            'password_confirmation' => 'short12',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'New password must be at least 8 characters.');
    }

    public function test_new_password_cannot_exceed_255_characters(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());
        $long   = str_repeat('a', 256);

        $this->changeWith($tokens['access_token'], $this->payload([
            'password'              => $long,
            'password_confirmation' => $long,
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'New password may not exceed 255 characters.');
    }

    public function test_new_password_confirmation_must_match(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->changeWith($tokens['access_token'], $this->payload(['password_confirmation' => 'Different#Pass1']))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'New password confirmation does not match.');
    }

    public function test_new_password_confirmation_is_required(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->changeWith($tokens['access_token'], [
            'current_password' => self::OLD_PASSWORD,
            'password'         => self::NEW_PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'New password confirmation does not match.');
    }

    public function test_invalid_request_does_not_change_password_or_sessions(): void
    {
        $admin   = $this->makeAdmin();
        $tokens  = $this->loginAs($admin, 'chrome');
        $another = $this->loginAs($admin, 'firefox');

        $this->changeWith($tokens['access_token'], $this->payload(['password_confirmation' => 'nope']))
            ->assertStatus(422);

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
        $this->assertNotNull($this->tokenRecord($another['access_token']));
    }

    public function test_requires_authentication(): void
    {
        $this->postJson(self::URL, $this->payload())->assertUnauthorized();
    }

    public function test_rejects_invalid_token(): void
    {
        $this->changeWith('1|' . str_repeat('x', 40), $this->payload())->assertUnauthorized();
    }

    public function test_rejects_expired_access_token(): void
    {
        config(['admin.auth.access_token_ttl' => 10]);

        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->travel(11)->minutes();

        $this->changeWith($tokens['access_token'], $this->payload())->assertUnauthorized();

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_rejects_access_token_after_logout(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->app['auth']->forgetGuards();
        $this->withToken($tokens['access_token'])->postJson('/api/v1/admin/auth/logout')->assertOk();

        $this->changeWith($tokens['access_token'], $this->payload())->assertUnauthorized();
    }

    public function test_locked_admin_is_forbidden(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $admin->forceFill(['status' => 'LOCKED'])->save();

        $this->changeWith($tokens['access_token'], $this->payload())
            ->assertForbidden()
            ->assertExactJson([
                'success' => false,
                'message' => 'Administrator account is not active.',
                'data'    => null,
            ]);

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_does_not_accept_get_method(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->withToken($tokens['access_token'])->getJson(self::URL)->assertStatus(405);
    }

    public function test_throttle_returns_429_after_five_attempts(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        for ($i = 0; $i < 5; $i++) {
            $this->changeWith($tokens['access_token'], $this->payload(['current_password' => 'Wrong#Pass' . $i]))
                ->assertStatus(422);
        }

        $this->changeWith($tokens['access_token'], $this->payload())->assertStatus(429);
    }

    public function test_throttle_is_tracked_per_admin(): void
    {
        $first  = $this->loginAs($this->makeAdmin());
        $second = $this->loginAs($this->makeAdmin());

        for ($i = 0; $i < 5; $i++) {
            $this->changeWith($first['access_token'], $this->payload(['current_password' => 'Wrong#Pass' . $i]));
        }

        $this->changeWith($second['access_token'], $this->payload())->assertOk();
    }
}
