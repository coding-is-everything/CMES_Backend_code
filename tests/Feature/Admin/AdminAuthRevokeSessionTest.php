<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthRevokeSessionTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const REFRESH_URL = '/api/v1/admin/auth/refresh';

    private const SESSIONS_URL = '/api/v1/admin/auth/sessions';

    private const PASSWORD = 'Secret@12345';

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'REV' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Revoke Admin',
            'email'         => "revoke{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    /**
     * @return array{access_token: string, refresh_token: string, session_id: string}
     */
    private function loginAs(AdminUser $admin, ?string $deviceName = null): array
    {
        $payload = ['email' => $admin->email, 'password' => self::PASSWORD];

        if ($deviceName !== null) {
            $payload['device_name'] = $deviceName;
        }

        $response = $this->postJson(self::LOGIN_URL, $payload)->assertOk();
        $access   = $response->json('data.access_token');
        $name     = PersonalAccessToken::findToken($access)->name;

        return [
            'access_token'  => $access,
            'refresh_token' => $response->json('data.refresh_token'),
            'session_id'    => substr($name, strrpos($name, ':') + 1),
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
    private function revoke(string $accessToken, string $sessionId)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($accessToken)
            ->deleteJson("/api/v1/admin/auth/sessions/{$sessionId}");
    }

    public function test_admin_can_revoke_another_device_session(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $this->revoke($current['access_token'], $other['session_id'])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Session revoked successfully.',
                'data'    => [
                    'session_id'      => $other['session_id'],
                    'revoked_current' => false,
                ],
            ]);
    }

    public function test_both_tokens_of_the_revoked_session_are_deleted(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $this->revoke($current['access_token'], $other['session_id'])->assertOk();

        $this->assertNull($this->tokenRecord($other['access_token']));
        $this->assertNull($this->tokenRecord($other['refresh_token']));
    }

    public function test_calling_session_and_other_sessions_are_untouched(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $target  = $this->loginAs($admin, 'firefox');
        $bystand = $this->loginAs($admin, 'mobile');

        $this->revoke($current['access_token'], $target['session_id'])->assertOk();

        $this->assertNotNull($this->tokenRecord($current['access_token']));
        $this->assertNotNull($this->tokenRecord($current['refresh_token']));
        $this->assertNotNull($this->tokenRecord($bystand['access_token']));
        $this->assertNotNull($this->tokenRecord($bystand['refresh_token']));
        $this->assertSame(4, $admin->tokens()->count());
    }

    public function test_revoked_device_loses_access_and_cannot_refresh(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $this->revoke($current['access_token'], $other['session_id'])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($other['access_token'])->getJson(self::SESSIONS_URL)->assertUnauthorized();

        $this->postJson(self::REFRESH_URL, ['refresh_token' => $other['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid or expired refresh token.');
    }

    public function test_revoked_session_disappears_from_the_session_list(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $this->revoke($current['access_token'], $other['session_id'])->assertOk();

        $this->app['auth']->forgetGuards();
        $sessions = $this->withToken($current['access_token'])
            ->getJson(self::SESSIONS_URL)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data');

        $this->assertSame($current['session_id'], $sessions[0]['session_id']);
    }

    public function test_session_id_from_the_list_endpoint_can_be_revoked(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $this->loginAs($admin, 'firefox');

        $this->app['auth']->forgetGuards();
        $listed = $this->withToken($current['access_token'])->getJson(self::SESSIONS_URL)->json('data');

        $target = collect($listed)->firstWhere('is_current', false)['session_id'];

        $this->revoke($current['access_token'], $target)->assertOk();
    }

    public function test_admin_can_revoke_the_current_session_which_acts_as_logout(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $this->revoke($current['access_token'], $current['session_id'])
            ->assertOk()
            ->assertJsonPath('data.revoked_current', true);

        $this->assertNull($this->tokenRecord($current['access_token']));
        $this->assertNull($this->tokenRecord($current['refresh_token']));
        $this->assertNotNull($this->tokenRecord($other['access_token']));

        $this->revoke($current['access_token'], $current['session_id'])->assertUnauthorized();
    }

    public function test_session_id_is_case_insensitive(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');

        $this->revoke($current['access_token'], strtoupper($current['session_id']))
            ->assertOk()
            ->assertJsonPath('data.session_id', $current['session_id'])
            ->assertJsonPath('data.revoked_current', true);
    }

    public function test_revoking_a_refreshed_session_works(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $refreshed = $this->postJson(self::REFRESH_URL, ['refresh_token' => $other['refresh_token']])
            ->assertOk();

        $this->revoke($current['access_token'], $other['session_id'])->assertOk();

        $this->assertNull($this->tokenRecord($refreshed->json('data.access_token')));
        $this->assertNull($this->tokenRecord($refreshed->json('data.refresh_token')));
    }

    public function test_another_admins_session_cannot_be_revoked(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        $mine   = $this->loginAs($admin, 'mine');
        $theirs = $this->loginAs($other, 'theirs');

        $this->revoke($mine['access_token'], $theirs['session_id'])
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Session not found.',
                'data'    => null,
            ]);

        $this->assertNotNull($this->tokenRecord($theirs['access_token']));
        $this->assertNotNull($this->tokenRecord($theirs['refresh_token']));
        $this->assertSame(2, $other->tokens()->count());
    }

    public function test_unknown_session_returns_404_and_changes_nothing(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');

        $this->revoke($current['access_token'], (string) Str::uuid())
            ->assertNotFound()
            ->assertJsonPath('message', 'Session not found.');

        $this->assertSame(2, $admin->tokens()->count());
    }

    public function test_already_revoked_session_returns_404(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $this->revoke($current['access_token'], $other['session_id'])->assertOk();

        $this->revoke($current['access_token'], $other['session_id'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Session not found.');
    }

    public function test_non_uuid_session_id_does_not_match_the_route(): void
    {
        $current = $this->loginAs($this->makeAdmin(), 'chrome');

        $this->revoke($current['access_token'], 'not-a-uuid')->assertNotFound();
        $this->revoke($current['access_token'], '%')->assertNotFound();
    }

    public function test_tokens_outside_the_admin_naming_convention_are_never_deleted(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');

        $uuid        = (string) Str::uuid();
        $integration = $admin->createToken('integration:crm:' . $uuid, ['*'], now()->addDay());

        $this->revoke($current['access_token'], $uuid)->assertNotFound();

        $this->assertNotNull($this->tokenRecord($integration->plainTextToken));
    }

    public function test_requires_authentication(): void
    {
        $this->deleteJson('/api/v1/admin/auth/sessions/' . Str::uuid())->assertUnauthorized();
    }

    public function test_rejects_invalid_token(): void
    {
        $this->revoke('1|' . str_repeat('x', 40), (string) Str::uuid())->assertUnauthorized();
    }

    public function test_rejects_expired_access_token(): void
    {
        config(['admin.auth.access_token_ttl' => 10]);

        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $this->travel(11)->minutes();

        $this->revoke($current['access_token'], $other['session_id'])->assertUnauthorized();

        $this->assertSame(4, $admin->tokens()->count());
    }

    public function test_locked_admin_is_forbidden_and_nothing_is_revoked(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $other   = $this->loginAs($admin, 'firefox');

        $admin->forceFill(['status' => 'LOCKED'])->save();

        $this->revoke($current['access_token'], $other['session_id'])
            ->assertForbidden()
            ->assertExactJson([
                'success' => false,
                'message' => 'Administrator account is not active.',
                'data'    => null,
            ]);

        $this->assertNotNull($this->tokenRecord($other['access_token']));
    }

    public function test_does_not_accept_get_or_post_methods(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'chrome');
        $url     = '/api/v1/admin/auth/sessions/' . $current['session_id'];

        $this->withToken($current['access_token'])->postJson($url)->assertStatus(405);

        $this->assertNotNull($this->tokenRecord($current['access_token']));
    }
}
