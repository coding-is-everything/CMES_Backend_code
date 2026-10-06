<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminPasswordResetMail;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const FORGOT_URL = '/api/v1/admin/auth/forgot-password';

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const REFRESH_URL = '/api/v1/admin/auth/refresh';

    private const URL = '/api/v1/admin/auth/reset-password';

    private const OLD_PASSWORD = 'Secret@12345';

    private const NEW_PASSWORD = 'Brand#New9876';

    private const INVALID_TOKEN_MESSAGE = 'Invalid or expired password reset token.';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'RST' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Reset Admin',
            'email'         => "reset{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::OLD_PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    /**
     * Run the real forgot-password flow and return the raw emailed token.
     */
    private function issueToken(AdminUser $admin): string
    {
        $this->postJson(self::FORGOT_URL, ['email' => $admin->email])->assertOk();

        $token = null;

        Mail::assertSent(AdminPasswordResetMail::class, function (AdminPasswordResetMail $mail) use ($admin, &$token) {
            if (! $mail->hasTo($admin->email)) {
                return false;
            }

            parse_str(parse_url($mail->resetUrl, PHP_URL_QUERY), $query);
            $token = $query['token'];

            return true;
        });

        $this->assertNotNull($token);

        return $token;
    }

    private function payload(AdminUser $admin, string $token, array $overrides = []): array
    {
        return array_merge([
            'email'                 => $admin->email,
            'token'                 => $token,
            'password'              => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ], $overrides);
    }

    private function login(AdminUser $admin, string $password)
    {
        return $this->postJson(self::LOGIN_URL, [
            'email'    => $admin->email,
            'password' => $password,
        ]);
    }

    public function test_admin_can_reset_password_with_a_valid_token(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Password has been reset successfully. Please log in with your new password.',
                'data'    => null,
            ]);

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_admin_can_log_in_with_new_password_and_not_the_old_one(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $this->login($admin, self::NEW_PASSWORD)
            ->assertOk()
            ->assertJsonPath('data.admin.id', $admin->id);

        $this->login($admin, self::OLD_PASSWORD)
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Invalid email or password.');
    }

    public function test_token_is_single_use(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $this->postJson(self::URL, $this->payload($admin, $token, [
            'password'              => 'Another#Pass1',
            'password_confirmation' => 'Another#Pass1',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);

        // Second attempt must not have changed the password.
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_token_is_marked_used_in_the_database(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $this->assertNotNull(
            DB::table('admin_password_resets')->where('token_hash', hash('sha256', $token))->value('used_at')
        );
    }

    public function test_all_existing_sessions_are_revoked_after_reset(): void
    {
        $admin = $this->makeAdmin();

        $session = $this->login($admin, self::OLD_PASSWORD)->assertOk();
        $this->assertSame(2, $admin->tokens()->count());

        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $this->assertSame(0, $admin->tokens()->count());

        $this->postJson(self::REFRESH_URL, ['refresh_token' => $session->json('data.refresh_token')])
            ->assertStatus(422)
            ->assertJsonPath('errors.refresh_token.0', 'Invalid or expired refresh token.');
    }

    public function test_reset_does_not_revoke_sessions_of_other_admins(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        $this->login($other, self::OLD_PASSWORD)->assertOk();

        $token = $this->issueToken($admin);
        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $this->assertSame(2, $other->tokens()->count());
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $other->fresh()->password_hash));
    }

    public function test_failed_login_lockout_is_cleared_after_reset(): void
    {
        $admin = $this->makeAdmin();

        RateLimiter::hit('admin-login:' . $admin->email, 60);
        RateLimiter::hit('admin-login:' . $admin->email, 60);
        RateLimiter::hit('admin-login:' . $admin->email, 60);
        RateLimiter::hit('admin-login:' . $admin->email, 60);
        RateLimiter::hit('admin-login:' . $admin->email, 60);

        $token = $this->issueToken($admin);
        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $this->login($admin, self::NEW_PASSWORD)->assertOk();
    }

    public function test_a_newer_reset_link_invalidates_the_older_one(): void
    {
        config(['admin.password_reset.cooldown' => 60]);

        $admin = $this->makeAdmin();
        $old   = $this->issueToken($admin);

        $this->travel(61)->seconds();
        Mail::fake();
        $new = $this->issueToken($admin);

        $this->assertNotSame($old, $new);

        $this->postJson(self::URL, $this->payload($admin, $old))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);

        $this->postJson(self::URL, $this->payload($admin, $new))->assertOk();
    }

    public function test_successful_reset_voids_any_other_outstanding_links(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        // Simulate a second, still-valid link for the same admin.
        DB::table('admin_password_resets')->insert([
            'admin_user_id' => $admin->id,
            'token_hash'    => hash('sha256', 'second-link-token'),
            'expires_at'    => now()->addHour(),
            'created_at'    => now(),
        ]);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $this->postJson(self::URL, $this->payload($admin, 'second-link-token'))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);
    }

    public function test_expired_token_is_rejected(): void
    {
        config(['admin.password_reset.ttl' => 30]);

        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->travel(31)->minutes();

        $this->postJson(self::URL, $this->payload($admin, $token))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_token_still_works_just_before_expiry(): void
    {
        config(['admin.password_reset.ttl' => 30]);

        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->travel(29)->minutes();

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();
    }

    public function test_unknown_token_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, str_repeat('x', 64)))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);
    }

    public function test_token_cannot_be_used_with_a_different_admins_email(): void
    {
        $admin    = $this->makeAdmin();
        $attacker = $this->makeAdmin();
        $token    = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($attacker, $token))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $attacker->fresh()->password_hash));
    }

    public function test_unknown_email_gets_the_same_error_as_a_bad_token(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $badEmail = $this->postJson(self::URL, $this->payload($admin, $token, ['email' => 'ghost@example.com']));
        $badToken = $this->postJson(self::URL, $this->payload($admin, 'nope'));

        $this->assertSame($badEmail->getStatusCode(), $badToken->getStatusCode());
        $this->assertSame($badEmail->json('errors'), $badToken->json('errors'));
    }

    public function test_email_is_trimmed_and_case_insensitive(): void
    {
        $admin = $this->makeAdmin(['email' => 'casing@example.com']);
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token, ['email' => '  CaSing@Example.com ']))
            ->assertOk();
    }

    public function test_token_is_trimmed(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, '  ' . $token . ' '))->assertOk();
    }

    public function test_locked_admin_cannot_reset_password(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $admin->forceFill(['status' => 'LOCKED'])->save();

        $this->postJson(self::URL, $this->payload($admin, $token))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_inactive_admin_cannot_reset_password(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $admin->forceFill(['status' => 'INACTIVE'])->save();

        $this->postJson(self::URL, $this->payload($admin, $token))->assertStatus(422);
    }

    public function test_soft_deleted_admin_cannot_reset_password(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $admin->delete();

        $this->postJson(self::URL, $this->payload($admin, $token))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', self::INVALID_TOKEN_MESSAGE);
    }

    public function test_failed_attempt_leaves_token_usable(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        // Validation failure (password too short) must not burn the token.
        $this->postJson(self::URL, $this->payload($admin, $token, [
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]))->assertStatus(422);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();
    }

    public function test_password_is_stored_hashed(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();

        $stored = $admin->fresh()->password_hash;

        $this->assertNotSame(self::NEW_PASSWORD, $stored);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $stored));
    }

    public function test_password_whitespace_is_preserved(): void
    {
        $admin    = $this->makeAdmin();
        $token    = $this->issueToken($admin);
        $password = '  spaced pass 1  ';

        $this->postJson(self::URL, $this->payload($admin, $token, [
            'password'              => $password,
            'password_confirmation' => $password,
        ]))->assertOk();

        $this->assertTrue(Hash::check($password, $admin->fresh()->password_hash));
    }

    public function test_email_is_required(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, 'x', ['email' => null]))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email address is required.');
    }

    public function test_email_must_be_valid(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, 'x', ['email' => 'nope']))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Please enter a valid email address.');
    }

    public function test_token_is_required(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, '', ['token' => null]))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', 'Password reset token is required.');
    }

    public function test_blank_token_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, '   '))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', 'Password reset token is required.');
    }

    public function test_token_must_be_a_string(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, 'x', ['token' => ['a']]))
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', 'Invalid password reset token format.');
    }

    public function test_token_cannot_exceed_255_characters(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, str_repeat('a', 256)))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_password_is_required(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, [
            'email' => $admin->email,
            'token' => 'x',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password is required.');
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, 'x', [
            'password'              => 'short12',
            'password_confirmation' => 'short12',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password must be at least 8 characters.');
    }

    public function test_password_cannot_exceed_255_characters(): void
    {
        $admin = $this->makeAdmin();
        $long  = str_repeat('a', 256);

        $this->postJson(self::URL, $this->payload($admin, 'x', [
            'password'              => $long,
            'password_confirmation' => $long,
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password may not exceed 255 characters.');
    }

    public function test_password_confirmation_must_match(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, $this->payload($admin, 'x', [
            'password_confirmation' => 'Different#Pass1',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password confirmation does not match.');
    }

    public function test_password_confirmation_is_required(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, [
            'email'    => $admin->email,
            'token'    => 'x',
            'password' => self::NEW_PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password confirmation does not match.');
    }

    public function test_invalid_request_does_not_change_the_password(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token, ['password_confirmation' => 'nope']))
            ->assertStatus(422);

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->fresh()->password_hash));
    }

    public function test_endpoint_is_public_and_needs_no_bearer_token(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->issueToken($admin);

        $this->postJson(self::URL, $this->payload($admin, $token))->assertOk();
    }

    public function test_endpoint_does_not_accept_get_method(): void
    {
        $this->getJson(self::URL)->assertStatus(405);
    }

    public function test_route_level_throttle_returns_429_after_ten_requests(): void
    {
        $admin = $this->makeAdmin();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(self::URL, $this->payload($admin, 'guess' . $i))->assertStatus(422);
        }

        $this->postJson(self::URL, $this->payload($admin, 'guess-final'))->assertStatus(429);
    }
}
