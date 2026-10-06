<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthRefreshTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const URL = '/api/v1/admin/auth/refresh';

    private const PASSWORD = 'Secret@12345';

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'RFR' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Refresh Admin',
            'email'         => "refresh{$counter}@example.com",
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

    private function refreshTokenRecord(string $plainTextToken): ?PersonalAccessToken
    {
        return PersonalAccessToken::findToken($plainTextToken);
    }

    public function test_admin_can_refresh_tokens_with_valid_refresh_token(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $response = $this->postJson(self::URL, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admin access token refreshed successfully.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.admin.id', $admin->id)
            ->assertJsonPath('data.admin.email', $admin->email)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'admin' => ['id', 'admin_code', 'full_name', 'email', 'mobile_number', 'status', 'roles'],
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'access_token_expires_at',
                    'refresh_token_expires_at',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.access_token'));
        $this->assertNotEmpty($response->json('data.refresh_token'));
        $this->assertNotSame($tokens['access_token'], $response->json('data.access_token'));
        $this->assertNotSame($tokens['refresh_token'], $response->json('data.refresh_token'));
        $this->assertArrayNotHasKey('password_hash', $response->json('data.admin'));
    }

    public function test_refresh_rotates_refresh_token_and_old_one_is_rejected(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk();

        $this->assertNull($this->refreshTokenRecord($tokens['refresh_token']));

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid or expired refresh token.');
    }

    public function test_refresh_revokes_previous_access_token(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->assertNotNull($this->refreshTokenRecord($tokens['access_token']));

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk();

        $this->assertNull($this->refreshTokenRecord($tokens['access_token']));
    }

    public function test_refresh_keeps_exactly_one_token_pair_for_the_session(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->assertSame(2, $admin->tokens()->count());

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk();

        $this->assertSame(1, $admin->tokens()->where('name', 'like', 'admin-access:%')->count());
        $this->assertSame(1, $admin->tokens()->where('name', 'like', 'admin-refresh:%')->count());
    }

    public function test_new_tokens_carry_correct_abilities(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $response = $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk();

        $access  = $this->refreshTokenRecord($response->json('data.access_token'));
        $refresh = $this->refreshTokenRecord($response->json('data.refresh_token'));

        $this->assertTrue($access->can('admin:access'));
        $this->assertFalse($access->can('admin:refresh'));
        $this->assertTrue($refresh->can('admin:refresh'));
        $this->assertFalse($refresh->can('admin:access'));
    }

    public function test_new_tokens_use_configured_expiry(): void
    {
        config([
            'admin.auth.access_token_ttl'  => 15,
            'admin.auth.refresh_token_ttl' => 1000,
        ]);

        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->travel(1)->minutes();

        $response = $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk();

        $access  = $this->refreshTokenRecord($response->json('data.access_token'));
        $refresh = $this->refreshTokenRecord($response->json('data.refresh_token'));

        $this->assertEquals(now()->addMinutes(15)->timestamp, $access->expires_at->timestamp);
        $this->assertEquals(now()->addMinutes(1000)->timestamp, $refresh->expires_at->timestamp);
    }

    public function test_new_access_token_authenticates_against_sanctum(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $newAccess = $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk()
            ->json('data.access_token');

        $this->withToken($newAccess)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email);
    }

    public function test_session_id_is_preserved_across_refresh(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $before = $this->refreshTokenRecord($tokens['refresh_token'])->name;
        $uuid   = substr($before, strrpos($before, ':') + 1);

        $newRefresh = $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk()
            ->json('data.refresh_token');

        $this->assertSame(
            "admin-refresh:admin-web:{$uuid}",
            $this->refreshTokenRecord($newRefresh)->name
        );
    }

    public function test_device_name_defaults_to_the_one_from_login(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin, 'chrome-laptop');

        $newRefresh = $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk()
            ->json('data.refresh_token');

        $this->assertStringStartsWith(
            'admin-refresh:chrome-laptop:',
            $this->refreshTokenRecord($newRefresh)->name
        );
    }

    public function test_device_name_can_be_overridden_on_refresh(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin, 'chrome-laptop');

        $response = $this->postJson(self::URL, [
            'refresh_token' => $tokens['refresh_token'],
            'device_name'   => 'firefox-desktop',
        ])->assertOk();

        $this->assertStringStartsWith(
            'admin-access:firefox-desktop:',
            $this->refreshTokenRecord($response->json('data.access_token'))->name
        );
        $this->assertStringStartsWith(
            'admin-refresh:firefox-desktop:',
            $this->refreshTokenRecord($response->json('data.refresh_token'))->name
        );
    }

    public function test_device_name_with_colons_is_preserved(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin, 'host:chrome');

        $newRefresh = $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertOk()
            ->json('data.refresh_token');

        $this->assertStringStartsWith(
            'admin-refresh:host:chrome:',
            $this->refreshTokenRecord($newRefresh)->name
        );
    }

    public function test_refreshing_one_device_does_not_affect_another_device_session(): void
    {
        $admin  = $this->makeAdmin();
        $first  = $this->loginAs($admin, 'firefox');
        $second = $this->loginAs($admin, 'chrome');

        $this->postJson(self::URL, ['refresh_token' => $first['refresh_token']])
            ->assertOk();

        $this->assertNotNull($this->refreshTokenRecord($second['access_token']));
        $this->assertNotNull($this->refreshTokenRecord($second['refresh_token']));

        $this->postJson(self::URL, ['refresh_token' => $second['refresh_token']])
            ->assertOk();
    }

    public function test_refresh_token_is_trimmed(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->postJson(self::URL, [
            'refresh_token' => '  ' . $tokens['refresh_token'] . ' ',
        ])->assertOk();
    }

    public function test_refresh_token_is_required(): void
    {
        $this->postJson(self::URL, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token'])
            ->assertJsonPath('errors.refresh_token.0', 'Refresh token is required.');
    }

    public function test_blank_refresh_token_is_rejected(): void
    {
        $this->postJson(self::URL, ['refresh_token' => '   '])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Refresh token is required.');
    }

    public function test_refresh_token_must_be_a_string(): void
    {
        $this->postJson(self::URL, ['refresh_token' => ['not', 'a', 'string']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid refresh token format.');
    }

    public function test_refresh_token_cannot_exceed_500_characters(): void
    {
        $this->postJson(self::URL, ['refresh_token' => str_repeat('a', 501)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_device_name_cannot_exceed_100_characters(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->postJson(self::URL, [
            'refresh_token' => $tokens['refresh_token'],
            'device_name'   => str_repeat('a', 101),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.device_name.0', 'Device name may not exceed 100 characters.');
    }

    public function test_unknown_refresh_token_is_rejected(): void
    {
        $this->postJson(self::URL, ['refresh_token' => '1|' . str_repeat('x', 40)])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid or expired refresh token.');
    }

    public function test_access_token_cannot_be_used_as_refresh_token(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->postJson(self::URL, ['refresh_token' => $tokens['access_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid refresh token.');

        // The access token must survive the failed attempt.
        $this->assertNotNull($this->refreshTokenRecord($tokens['access_token']));
    }

    public function test_expired_refresh_token_is_rejected_and_deleted(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $record = $this->refreshTokenRecord($tokens['refresh_token']);
        $record->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Refresh token has expired.');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $record->id]);
    }

    public function test_refresh_token_expires_after_configured_ttl(): void
    {
        config(['admin.auth.refresh_token_ttl' => 30]);

        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $this->travel(31)->minutes();

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Refresh token has expired.');
    }

    public function test_locked_admin_cannot_refresh_and_token_is_revoked(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $admin->forceFill(['status' => 'LOCKED'])->save();

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Administrator account is not active.');

        $this->assertNull($this->refreshTokenRecord($tokens['refresh_token']));
    }

    public function test_inactive_admin_cannot_refresh(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $admin->forceFill(['status' => 'INACTIVE'])->save();

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Administrator account is not active.');
    }

    public function test_soft_deleted_admin_cannot_refresh(): void
    {
        $admin  = $this->makeAdmin();
        $tokens = $this->loginAs($admin);

        $admin->delete();

        $this->postJson(self::URL, ['refresh_token' => $tokens['refresh_token']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_refresh_token_without_session_id_is_rejected_and_deleted(): void
    {
        $admin = $this->makeAdmin();

        // Legacy-style name: no session UUID segment.
        $plain = $admin->createToken('admin-refresh:legacy', ['admin:refresh'], now()->addDay());

        $this->postJson(self::URL, ['refresh_token' => $plain->plainTextToken])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid refresh token session.');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $plain->accessToken->id]);
    }

    public function test_failed_refresh_does_not_issue_any_new_tokens(): void
    {
        $this->postJson(self::URL, ['refresh_token' => '1|' . str_repeat('x', 40)])
            ->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_route_level_throttle_returns_429_after_twenty_requests(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson(self::URL, ['refresh_token' => '1|' . str_repeat('x', 40)]);
        }

        $this->postJson(self::URL, ['refresh_token' => '1|' . str_repeat('x', 40)])
            ->assertStatus(429);
    }
}
