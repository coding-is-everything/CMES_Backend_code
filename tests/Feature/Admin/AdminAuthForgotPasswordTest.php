<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminPasswordResetMail;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAuthForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/admin/auth/forgot-password';

    private const GENERIC_MESSAGE = 'If the email address belongs to an administrator account, a password reset link has been sent.';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config(['admin.password_reset.frontend_url' => 'https://admin.example.test']);
    }

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'FGP' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Forgot Admin',
            'email'         => "forgot{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make('Secret@12345'),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    private function assertGenericSuccess($response): void
    {
        $response->assertOk()->assertExactJson([
            'success' => true,
            'message' => self::GENERIC_MESSAGE,
            'data'    => null,
        ]);
    }

    public function test_active_admin_receives_a_reset_email(): void
    {
        $admin = $this->makeAdmin();

        $this->assertGenericSuccess(
            $this->postJson(self::URL, ['email' => $admin->email])
        );

        Mail::assertSent(AdminPasswordResetMail::class, 1);
        Mail::assertSent(
            AdminPasswordResetMail::class,
            fn (AdminPasswordResetMail $mail) => $mail->hasTo($admin->email)
                && $mail->admin->is($admin)
        );
    }

    public function test_reset_link_points_to_the_frontend_with_token_and_email(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();

        Mail::assertSent(AdminPasswordResetMail::class, function (AdminPasswordResetMail $mail) use ($admin) {
            $parts = parse_url($mail->resetUrl);
            parse_str($parts['query'], $query);

            return $parts['scheme'] . '://' . $parts['host'] === 'https://admin.example.test'
                && $parts['path'] === '/reset-password'
                && $query['email'] === $admin->email
                && strlen($query['token']) === 64;
        });
    }

    public function test_only_the_token_hash_is_stored(): void
    {
        $admin = $this->makeAdmin();

        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();

        $rawToken = null;
        Mail::assertSent(AdminPasswordResetMail::class, function (AdminPasswordResetMail $mail) use (&$rawToken) {
            parse_str(parse_url($mail->resetUrl, PHP_URL_QUERY), $query);
            $rawToken = $query['token'];

            return true;
        });

        $record = DB::table('admin_password_resets')->where('admin_user_id', $admin->id)->first();

        $this->assertNotNull($record);
        $this->assertNotSame($rawToken, $record->token_hash);
        $this->assertSame(hash('sha256', $rawToken), $record->token_hash);
        $this->assertNull($record->used_at);
    }

    public function test_reset_token_expires_after_configured_ttl(): void
    {
        config(['admin.password_reset.ttl' => 15]);

        $admin = $this->makeAdmin();

        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();

        $record = DB::table('admin_password_resets')->where('admin_user_id', $admin->id)->first();

        $this->assertEqualsWithDelta(
            now()->addMinutes(15)->timestamp,
            \Illuminate\Support\Carbon::parse($record->expires_at)->timestamp,
            5
        );

        Mail::assertSent(
            AdminPasswordResetMail::class,
            fn (AdminPasswordResetMail $mail) => $mail->expiresInMinutes === 15
        );
    }

    public function test_email_is_trimmed_and_case_insensitive(): void
    {
        $admin = $this->makeAdmin(['email' => 'mixed@example.com']);

        $this->assertGenericSuccess(
            $this->postJson(self::URL, ['email' => '  MiXed@Example.COM '])
        );

        Mail::assertSent(
            AdminPasswordResetMail::class,
            fn (AdminPasswordResetMail $mail) => $mail->hasTo('mixed@example.com')
        );
    }

    public function test_unknown_email_gets_identical_response_and_no_mail(): void
    {
        $this->assertGenericSuccess(
            $this->postJson(self::URL, ['email' => 'nobody@example.com'])
        );

        Mail::assertNothingSent();
        $this->assertDatabaseCount('admin_password_resets', 0);
    }

    public function test_locked_admin_gets_identical_response_and_no_mail(): void
    {
        $admin = $this->makeAdmin(['status' => 'LOCKED']);

        $this->assertGenericSuccess($this->postJson(self::URL, ['email' => $admin->email]));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('admin_password_resets', 0);
    }

    public function test_inactive_admin_gets_identical_response_and_no_mail(): void
    {
        $admin = $this->makeAdmin(['status' => 'INACTIVE']);

        $this->assertGenericSuccess($this->postJson(self::URL, ['email' => $admin->email]));

        Mail::assertNothingSent();
    }

    public function test_soft_deleted_admin_gets_identical_response_and_no_mail(): void
    {
        $admin = $this->makeAdmin();
        $admin->delete();

        $this->assertGenericSuccess($this->postJson(self::URL, ['email' => $admin->email]));

        Mail::assertNothingSent();
    }

    public function test_response_is_the_same_for_existing_and_unknown_accounts(): void
    {
        $admin = $this->makeAdmin();

        $known   = $this->postJson(self::URL, ['email' => $admin->email]);
        $unknown = $this->postJson(self::URL, ['email' => 'ghost@example.com']);

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame($known->getContent(), $unknown->getContent());
    }

    public function test_repeat_request_within_cooldown_sends_no_second_email(): void
    {
        config(['admin.password_reset.cooldown' => 60]);

        $admin = $this->makeAdmin();

        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();
        $this->assertGenericSuccess($this->postJson(self::URL, ['email' => $admin->email]));

        Mail::assertSent(AdminPasswordResetMail::class, 1);
        $this->assertSame(1, DB::table('admin_password_resets')->where('admin_user_id', $admin->id)->count());
    }

    public function test_new_request_after_cooldown_replaces_the_previous_link(): void
    {
        config(['admin.password_reset.cooldown' => 60]);

        $admin = $this->makeAdmin();

        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();
        $first = DB::table('admin_password_resets')->where('admin_user_id', $admin->id)->value('token_hash');

        $this->travel(61)->seconds();

        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();

        Mail::assertSent(AdminPasswordResetMail::class, 2);

        $hashes = DB::table('admin_password_resets')->where('admin_user_id', $admin->id)->pluck('token_hash');

        $this->assertCount(1, $hashes);
        $this->assertNotSame($first, $hashes->first());
    }

    public function test_each_admin_gets_their_own_token(): void
    {
        $first  = $this->makeAdmin();
        $second = $this->makeAdmin();

        $this->postJson(self::URL, ['email' => $first->email])->assertOk();
        $this->postJson(self::URL, ['email' => $second->email])->assertOk();

        Mail::assertSent(AdminPasswordResetMail::class, 2);
        $this->assertSame(2, DB::table('admin_password_resets')->distinct()->count('token_hash'));
    }

    public function test_request_does_not_touch_the_password_or_existing_sessions(): void
    {
        $admin = $this->makeAdmin();
        $admin->createToken('admin-access:web:abc', ['admin:access'], now()->addHour());

        $hash = $admin->password_hash;

        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();

        $this->assertSame($hash, $admin->fresh()->password_hash);
        $this->assertSame(1, $admin->tokens()->count());
    }

    public function test_mail_failure_is_not_revealed_to_the_caller(): void
    {
        $admin = $this->makeAdmin();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->assertGenericSuccess($this->postJson(self::URL, ['email' => $admin->email]));
    }

    public function test_email_is_required(): void
    {
        $this->postJson(self::URL, [])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email address is required.');
    }

    public function test_blank_email_is_rejected(): void
    {
        $this->postJson(self::URL, ['email' => '   '])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email address is required.');
    }

    public function test_email_must_be_valid(): void
    {
        $this->postJson(self::URL, ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Please enter a valid email address.');
    }

    public function test_email_must_be_a_string(): void
    {
        $this->postJson(self::URL, ['email' => ['a@example.com']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_cannot_exceed_150_characters(): void
    {
        $this->postJson(self::URL, ['email' => str_repeat('a', 142) . '@test.com'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email address may not exceed 150 characters.');
    }

    public function test_invalid_request_sends_no_mail(): void
    {
        $this->postJson(self::URL, ['email' => 'bad'])->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_endpoint_is_public_and_needs_no_token(): void
    {
        $admin = $this->makeAdmin();

        // No Authorization header is sent anywhere in this class; make that explicit.
        $this->postJson(self::URL, ['email' => $admin->email])->assertOk();
    }

    public function test_endpoint_does_not_accept_get_method(): void
    {
        $this->getJson(self::URL)->assertStatus(405);
    }

    public function test_route_level_throttle_returns_429_after_five_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::URL, ['email' => 'spam@example.com'])->assertOk();
        }

        $this->postJson(self::URL, ['email' => 'spam@example.com'])->assertStatus(429);
    }

    public function test_throttle_is_tracked_per_email(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::URL, ['email' => 'spam@example.com']);
        }

        $this->postJson(self::URL, ['email' => 'other@example.com'])->assertOk();
    }
}
