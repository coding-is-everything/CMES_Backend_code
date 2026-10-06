<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthLogoutAllTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const REFRESH_URL = '/api/v1/admin/auth/refresh';

    private const URL = '/api/v1/admin/auth/logout-all';

    private const PASSWORD = 'Secret@12345';

    private function makeAdmin(): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create([
            'admin_code'    => 'LGA' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Logout All Admin',
            'email'         => "logoutall{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::PASSWORD),
            'status'        => 'ACTIVE',
        ]);
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function loginAs(AdminUser $admin, ?string $deviceName = null): array
    {
        $payload = ['email' => $admin->email, 'password' => self::PASSWORD];

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
     * authenticates from the bearer token again.
     */
    private function logoutAllWith(string $accessToken)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($accessToken)->postJson(self::URL);
    }

    public function test_admin_can_logout_from_all_sessions(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->logoutAllWith($tokens['access_token'])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'All admin sessions revoked successfully.',
                'data'    => ['revoked_tokens' => 2],
            ]);
    }

    public function test_all_tokens_on_every_device_are_revoked(): void
    {
        $admin  = $this->makeAdmin();
        $first  = $this->loginAs($admin, 'firefox');
        $second = $this->loginAs($admin, 'chrome');
        $third  = $this->loginAs($admin, 'mobile');

        $this->assertSame(6, $admin->tokens()->count());

        $this->logoutAllWith($first['access_token'])
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 6);

        foreach ([$first, $second, $third] as $tokens) {
            $this->assertNull($this->tokenRecord($tokens['access_token']));
            $this->assertNull($this->tokenRecord($tokens['refresh_token']));
        }

        $this->assertSame(0, $admin->tokens()->count());
    }

    public function test_other_devices_cannot_use_their_tokens_afterwards(): void
    {
        $admin  = $this->makeAdmin();
        $first  = $this->loginAs($admin, 'firefox');
        $second = $this->loginAs($admin, 'chrome');

        $this->logoutAllWith($first['access_token'])->assertOk();

        $this->logoutAllWith($second['access_token'])->assertUnauthorized();

        $this->postJson(self::REFRESH_URL, ['refresh_token' => $second['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid or expired refresh token.');
    }

    public function test_calling_logout_all_twice_returns_unauthorized_the_second_time(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->logoutAllWith($tokens['access_token'])->assertOk();
        $this->logoutAllWith($tokens['access_token'])->assertUnauthorized();
    }

    public function test_logout_all_does_not_affect_other_admins(): void
    {
        $admin      = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $tokens      = $this->loginAs($admin);
        $otherTokens = $this->loginAs($otherAdmin);

        $this->logoutAllWith($tokens['access_token'])->assertOk();

        $this->assertNotNull($this->tokenRecord($otherTokens['access_token']));
        $this->assertNotNull($this->tokenRecord($otherTokens['refresh_token']));
        $this->assertSame(2, $otherAdmin->tokens()->count());
    }

    public function test_admin_can_login_again_after_logout_all(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->logoutAllWith($tokens['access_token'])->assertOk();

        $fresh = $this->loginAs($admin);

        $this->assertNotNull($this->tokenRecord($fresh['access_token']));
        $this->assertSame(2, $admin->tokens()->count());
    }

    public function test_logout_all_requires_authentication(): void
    {
        $this->postJson(self::URL)->assertUnauthorized();
    }

    public function test_logout_all_rejects_invalid_token(): void
    {
        $this->logoutAllWith('1|' . str_repeat('x', 40))->assertUnauthorized();
    }

    public function test_logout_all_rejects_expired_access_token(): void
    {
        config(['admin.auth.access_token_ttl' => 10]);

        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->travel(11)->minutes();

        $this->logoutAllWith($tokens['access_token'])->assertUnauthorized();

        // Rejected request must not have revoked anything.
        $this->assertSame(2, $admin->tokens()->count());
    }

    public function test_logout_all_does_not_accept_get_method(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->withToken($tokens['access_token'])
            ->getJson(self::URL)
            ->assertStatus(405);

        $this->assertSame(2, $admin->tokens()->count());
    }
}
