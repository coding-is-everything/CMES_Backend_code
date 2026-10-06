<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthSessionsTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const REFRESH_URL = '/api/v1/admin/auth/refresh';

    private const URL = '/api/v1/admin/auth/sessions';

    private const PASSWORD = 'Secret@12345';

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'SES' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Sessions Admin',
            'email'         => "sessions{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
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

    private function tokenRecord(string $plainTextToken): PersonalAccessToken
    {
        return PersonalAccessToken::findToken($plainTextToken);
    }

    /**
     * Sanctum caches the resolved user per guard; reset it so each request
     * authenticates from the bearer token again.
     */
    private function getSessions(string $accessToken)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($accessToken)->getJson(self::URL);
    }

    private function sessionIdOf(array $tokens): string
    {
        $name = $this->tokenRecord($tokens['access_token'])->name;

        return substr($name, strrpos($name, ':') + 1);
    }

    public function test_admin_can_list_own_sessions(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->getSessions($tokens['access_token'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admin sessions retrieved successfully.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.session_id', $this->sessionIdOf($tokens))
            ->assertJsonPath('data.0.device_name', 'admin-web')
            ->assertJsonPath('data.0.is_current', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [[
                    'session_id',
                    'device_name',
                    'is_current',
                    'issued_at',
                    'last_used_at',
                    'access_token_expires_at',
                    'refresh_token_expires_at',
                ]],
            ]);
    }

    public function test_each_device_is_one_session_not_one_per_token(): void
    {
        $admin = $this->makeAdmin();
        $this->loginAs($admin, 'firefox');
        $chrome = $this->loginAs($admin, 'chrome');
        $this->loginAs($admin, 'mobile');

        $this->assertSame(6, $admin->tokens()->count());

        $this->getSessions($chrome['access_token'])
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_only_the_calling_session_is_marked_current(): void
    {
        $admin  = $this->makeAdmin();
        $first  = $this->loginAs($admin, 'firefox');
        $second = $this->loginAs($admin, 'chrome');

        $response = $this->getSessions($second['access_token'])->assertOk();

        $current = array_values(array_filter($response->json('data'), fn ($s) => $s['is_current']));

        $this->assertCount(1, $current);
        $this->assertSame($this->sessionIdOf($second), $current[0]['session_id']);
        $this->assertSame('chrome', $current[0]['device_name']);

        $this->assertNotContains($this->sessionIdOf($first), array_column($current, 'session_id'));
    }

    public function test_current_session_is_listed_first(): void
    {
        $admin  = $this->makeAdmin();
        $this->loginAs($admin, 'firefox');
        $this->loginAs($admin, 'mobile');
        $chrome = $this->loginAs($admin, 'chrome');

        $this->getSessions($chrome['access_token'])
            ->assertOk()
            ->assertJsonPath('data.0.is_current', true)
            ->assertJsonPath('data.0.device_name', 'chrome');
    }

    public function test_other_sessions_are_ordered_by_most_recently_used(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'current');
        $old     = $this->loginAs($admin, 'old');
        $recent  = $this->loginAs($admin, 'recent');
        $never   = $this->loginAs($admin, 'never');

        $this->tokenRecord($old['access_token'])->forceFill(['last_used_at' => now()->subDays(3)])->save();
        $this->tokenRecord($recent['access_token'])->forceFill(['last_used_at' => now()->subMinutes(5)])->save();
        $this->tokenRecord($never['access_token'])->forceFill(['last_used_at' => null])->save();
        $this->tokenRecord($never['refresh_token'])->forceFill(['last_used_at' => null])->save();

        $devices = array_column(
            $this->getSessions($current['access_token'])->assertOk()->json('data'),
            'device_name'
        );

        $this->assertSame(['current', 'recent', 'old', 'never'], $devices);
    }

    public function test_last_used_at_is_the_latest_of_the_token_pair(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin, 'chrome');

        $this->tokenRecord($tokens['access_token'])->forceFill(['last_used_at' => now()->subHours(2)])->save();
        $this->tokenRecord($tokens['refresh_token'])->forceFill(['last_used_at' => now()->subHour()])->save();

        $this->app['auth']->forgetGuards();
        // Freeze what the API reports: the request itself bumps the access token's last_used_at.
        $used = $this->tokenRecord($tokens['refresh_token'])->last_used_at;

        $response = $this->getSessions($tokens['access_token'])->assertOk();

        $this->assertGreaterThanOrEqual(
            $used->getTimestamp(),
            \Illuminate\Support\Carbon::parse($response->json('data.0.last_used_at'))->getTimestamp()
        );
    }

    public function test_expiry_dates_match_the_tokens(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $response = $this->getSessions($tokens['access_token'])->assertOk();

        $this->assertEquals(
            $this->tokenRecord($tokens['access_token'])->expires_at->timestamp,
            \Illuminate\Support\Carbon::parse($response->json('data.0.access_token_expires_at'))->timestamp
        );
        $this->assertEquals(
            $this->tokenRecord($tokens['refresh_token'])->expires_at->timestamp,
            \Illuminate\Support\Carbon::parse($response->json('data.0.refresh_token_expires_at'))->timestamp
        );
    }

    public function test_refresh_keeps_the_same_session_id(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin, 'chrome');
        $before = $this->sessionIdOf($tokens);

        $refreshed = $this->postJson(self::REFRESH_URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk();

        $this->getSessions($refreshed->json('data.access_token'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.session_id', $before)
            ->assertJsonPath('data.0.device_name', 'chrome');
    }

    public function test_device_name_with_colons_is_reported_intact(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin, 'host:chrome');

        $this->getSessions($tokens['access_token'])
            ->assertOk()
            ->assertJsonPath('data.0.device_name', 'host:chrome');
    }

    public function test_sessions_of_other_admins_are_never_listed(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        $this->loginAs($other, 'their-laptop');
        $mine = $this->loginAs($admin, 'my-laptop');

        $response = $this->getSessions($mine['access_token'])->assertOk()->assertJsonCount(1, 'data');

        $this->assertSame(['my-laptop'], array_column($response->json('data'), 'device_name'));
    }

    public function test_fully_expired_sessions_are_not_listed(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'current');
        $stale   = $this->loginAs($admin, 'stale');

        $this->tokenRecord($stale['access_token'])->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->tokenRecord($stale['refresh_token'])->forceFill(['expires_at' => now()->subMinute()])->save();

        $devices = array_column(
            $this->getSessions($current['access_token'])->assertOk()->json('data'),
            'device_name'
        );

        $this->assertSame(['current'], $devices);
    }

    public function test_session_with_only_an_expired_access_token_is_still_listed(): void
    {
        $admin   = $this->makeAdmin();
        $current = $this->loginAs($admin, 'current');
        $idle    = $this->loginAs($admin, 'idle');

        // Access token lapsed but the refresh token can still revive the session.
        $this->tokenRecord($idle['access_token'])->forceFill(['expires_at' => now()->subMinute()])->save();

        $devices = array_column(
            $this->getSessions($current['access_token'])->assertOk()->json('data'),
            'device_name'
        );

        $this->assertEqualsCanonicalizing(['current', 'idle'], $devices);
    }

    public function test_tokens_outside_the_admin_naming_convention_are_ignored(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $admin->createToken('some-integration', ['*'], now()->addDay());
        $admin->createToken('admin-refresh:legacy', ['admin:refresh'], now()->addDay());

        $this->getSessions($tokens['access_token'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_response_never_exposes_token_values_or_ids(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $response = $this->getSessions($tokens['access_token'])->assertOk();
        $body     = $response->getContent();

        $this->assertStringNotContainsString($tokens['access_token'], $body);
        $this->assertStringNotContainsString($tokens['refresh_token'], $body);
        $this->assertStringNotContainsString(explode('|', $tokens['access_token'])[1], $body);
        $this->assertArrayNotHasKey('token', $response->json('data.0'));
        $this->assertArrayNotHasKey('id', $response->json('data.0'));
    }

    public function test_listing_is_read_only(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin, 'chrome');
        $this->loginAs($admin, 'firefox');

        $this->getSessions($tokens['access_token'])->assertOk();

        $this->assertSame(4, $admin->tokens()->count());
    }

    public function test_logged_out_session_disappears_from_the_list(): void
    {
        $admin   = $this->makeAdmin();
        $chrome  = $this->loginAs($admin, 'chrome');
        $firefox = $this->loginAs($admin, 'firefox');

        $this->app['auth']->forgetGuards();
        $this->withToken($firefox['access_token'])->postJson('/api/v1/admin/auth/logout')->assertOk();

        $this->getSessions($chrome['access_token'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device_name', 'chrome');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_rejects_invalid_token(): void
    {
        $this->getSessions('1|' . str_repeat('x', 40))->assertUnauthorized();
    }

    public function test_rejects_expired_access_token(): void
    {
        config(['admin.auth.access_token_ttl' => 10]);

        $tokens = $this->loginAs($this->makeAdmin());

        $this->travel(11)->minutes();

        $this->getSessions($tokens['access_token'])->assertUnauthorized();
    }

    public function test_rejects_access_token_after_logout_all(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->app['auth']->forgetGuards();
        $this->withToken($tokens['access_token'])->postJson('/api/v1/admin/auth/logout-all')->assertOk();

        $this->getSessions($tokens['access_token'])->assertUnauthorized();
    }

    public function test_locked_admin_is_forbidden(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $admin->forceFill(['status' => 'LOCKED'])->save();

        $this->getSessions($tokens['access_token'])
            ->assertForbidden()
            ->assertExactJson([
                'success' => false,
                'message' => 'Administrator account is not active.',
                'data'    => null,
            ]);
    }

    public function test_does_not_accept_post_method(): void
    {
        $tokens = $this->loginAs($this->makeAdmin());

        $this->withToken($tokens['access_token'])->postJson(self::URL)->assertStatus(405);
    }
}
