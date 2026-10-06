<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const REFRESH_URL = '/api/v1/admin/auth/refresh';

    private const URL = '/api/v1/admin/auth/logout';

    private const PASSWORD = 'Secret@12345';

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'LGO' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Logout Admin',
            'email'         => "logout{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    /**
     * Log the admin in through the API and return the token pair.
     *
     * @return array{access_token: string, refresh_token: string}
     */
    private function loginAs(AdminUser $admin, ?string $deviceName = null): array
    {
        $payload = [
            'email'    => $admin->email,
            'password' => self::PASSWORD,
        ];

        if ($deviceName !== null) {
            $payload['device_name'] = $deviceName;
        }

        $response = $this->postJson(self::LOGIN_URL, $payload)->assertOk();

        return [
            'access_token'  => $response->json('data.access_token'),
            'refresh_token' => $response->json('data.refresh_token'),
        ];
    }

    private function tokenRecord(string $plainTextToken): ?PersonalAccessToken
    {
        return PersonalAccessToken::findToken($plainTextToken);
    }

    /**
     * Sanctum caches the resolved user per guard; reset it so each request
     * in a test authenticates from the bearer token again.
     */
    private function logoutWith(string $accessToken)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($accessToken)->postJson(self::URL);
    }

    public function test_admin_can_logout_with_valid_access_token(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->logoutWith($tokens['access_token'])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Admin logout successful.',
                'data'    => null,
            ]);
    }

    public function test_logout_revokes_access_and_refresh_tokens_of_the_session(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->assertSame(2, $admin->tokens()->count());

        $this->logoutWith($tokens['access_token'])->assertOk();

        $this->assertNull($this->tokenRecord($tokens['access_token']));
        $this->assertNull($this->tokenRecord($tokens['refresh_token']));
        $this->assertSame(0, $admin->tokens()->count());
    }

    public function test_access_token_is_rejected_after_logout(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->logoutWith($tokens['access_token'])->assertOk();

        $this->logoutWith($tokens['access_token'])->assertUnauthorized();
    }

    public function test_refresh_token_cannot_be_used_after_logout(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->logoutWith($tokens['access_token'])->assertOk();

        $this->postJson(self::REFRESH_URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid or expired refresh token.');
    }

    public function test_logout_only_ends_the_current_device_session(): void
    {
        $admin  = $this->makeAdmin();
        $first  = $this->loginAs($admin, 'firefox');
        $second = $this->loginAs($admin, 'chrome');

        $this->logoutWith($first['access_token'])->assertOk();

        $this->assertNull($this->tokenRecord($first['access_token']));
        $this->assertNull($this->tokenRecord($first['refresh_token']));

        $this->assertNotNull($this->tokenRecord($second['access_token']));
        $this->assertNotNull($this->tokenRecord($second['refresh_token']));
        $this->assertSame(2, $admin->tokens()->count());
    }

    public function test_logout_does_not_affect_other_admins(): void
    {
        $admin      = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $tokens      = $this->loginAs($admin);
        $otherTokens = $this->loginAs($otherAdmin);

        $this->logoutWith($tokens['access_token'])->assertOk();

        $this->assertNotNull($this->tokenRecord($otherTokens['access_token']));
        $this->assertNotNull($this->tokenRecord($otherTokens['refresh_token']));
        $this->assertSame(2, $otherAdmin->tokens()->count());
    }

    public function test_logout_after_refresh_revokes_the_rotated_tokens(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $refreshed = $this->postJson(self::REFRESH_URL, [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk();

        $newAccess  = $refreshed->json('data.access_token');
        $newRefresh = $refreshed->json('data.refresh_token');

        $this->logoutWith($newAccess)->assertOk();

        $this->assertNull($this->tokenRecord($newAccess));
        $this->assertNull($this->tokenRecord($newRefresh));
        $this->assertSame(0, $admin->tokens()->count());
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson(self::URL)->assertUnauthorized();
    }

    public function test_logout_rejects_invalid_token(): void
    {
        $this->logoutWith('1|' . str_repeat('x', 40))->assertUnauthorized();
    }

    public function test_logout_rejects_expired_access_token(): void
    {
        config(['admin.auth.access_token_ttl' => 10]);
        config(['sanctum.expiration' => null]);

        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->travel(11)->minutes();

        $this->logoutWith($tokens['access_token'])->assertUnauthorized();
    }

    public function test_logout_does_not_accept_get_method(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->withToken($tokens['access_token'])
            ->getJson(self::URL)
            ->assertStatus(405);

        $this->assertNotNull($this->tokenRecord($tokens['access_token']));
    }

    public function test_logout_is_not_exposed_on_the_legacy_path(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->withToken($tokens['access_token'])
            ->postJson('/api/auth/logout')
            ->assertNotFound();

        $this->assertNotNull($this->tokenRecord($tokens['access_token']));
    }
}
