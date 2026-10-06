<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/admin/auth/register';

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const PASSWORD = 'Secret@12345';

    private const CLOSED_MESSAGE = 'Administrator registration is not available.';

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name'             => 'First Admin',
            'email'                 => 'first@example.com',
            'mobile_number'         => '9876543210',
            'password'              => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides);
    }

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'EXI' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Existing Admin',
            'email'         => "existing{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    private function makePermission(string $code): Permission
    {
        return Permission::create([
            'permission_code' => $code,
            'permission_name' => ucfirst($code),
            'module_name'     => 'Test',
        ]);
    }

    public function test_first_admin_can_be_registered(): void
    {
        $this->postJson(self::URL, $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'First administrator registered successfully. Please log in.')
            ->assertJsonPath('data.admin.full_name', 'First Admin')
            ->assertJsonPath('data.admin.email', 'first@example.com')
            ->assertJsonPath('data.admin.mobile_number', '9876543210')
            ->assertJsonPath('data.admin.status', 'ACTIVE')
            ->assertJsonPath('data.admin.admin_code', 'ADM0001')
            ->assertJsonPath('data.admin.roles.0.role_code', 'SUPER_ADMIN')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'admin' => ['id', 'admin_code', 'full_name', 'email', 'mobile_number', 'status', 'last_login_at', 'roles'],
                ],
            ]);

        $this->assertSame(1, AdminUser::count());
    }

    public function test_response_exposes_no_password_or_tokens(): void
    {
        $response = $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertArrayNotHasKey('password_hash', $response->json('data.admin'));
        $this->assertArrayNotHasKey('access_token', $response->json('data'));
        $this->assertArrayNotHasKey('refresh_token', $response->json('data'));
        $this->assertStringNotContainsString(self::PASSWORD, $response->getContent());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_password_is_stored_hashed(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();

        $stored = AdminUser::firstOrFail()->password_hash;

        $this->assertNotSame(self::PASSWORD, $stored);
        $this->assertTrue(Hash::check(self::PASSWORD, $stored));
    }

    public function test_registered_admin_can_log_in(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->postJson(self::LOGIN_URL, ['email' => 'first@example.com', 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('data.admin.roles.0.role_code', 'SUPER_ADMIN');
    }

    public function test_super_admin_role_is_created_when_missing(): void
    {
        $this->assertSame(0, Role::where('role_code', 'SUPER_ADMIN')->count());

        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertSame(1, Role::where('role_code', 'SUPER_ADMIN')->count());
        $this->assertDatabaseHas('admin_user_roles', [
            'admin_user_id' => AdminUser::firstOrFail()->id,
            'role_id'       => Role::where('role_code', 'SUPER_ADMIN')->value('id'),
        ]);
    }

    public function test_existing_super_admin_role_is_reused_not_duplicated(): void
    {
        $role = Role::create(['role_code' => 'SUPER_ADMIN', 'role_name' => 'Super Administrator']);

        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertSame(1, Role::where('role_code', 'SUPER_ADMIN')->count());
        $this->assertDatabaseHas('admin_user_roles', ['role_id' => $role->id]);
    }

    public function test_super_admin_receives_every_permission(): void
    {
        $this->makePermission('a.view');
        $this->makePermission('b.view');
        $this->makePermission('c.view');

        $this->postJson(self::URL, $this->payload())->assertCreated();

        $role = Role::where('role_code', 'SUPER_ADMIN')->firstOrFail();

        $this->assertSame(3, DB::table('role_permissions')->where('role_id', $role->id)->count());
        $this->assertCount(3, AdminUser::firstOrFail()->permissions());
    }

    public function test_already_granted_permissions_are_not_duplicated(): void
    {
        $role = Role::create(['role_code' => 'SUPER_ADMIN', 'role_name' => 'Super Administrator']);
        $a    = $this->makePermission('a.view');
        $this->makePermission('b.view');

        DB::table('role_permissions')->insert(['role_id' => $role->id, 'permission_id' => $a->id]);

        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertSame(2, DB::table('role_permissions')->where('role_id', $role->id)->count());
    }

    public function test_registration_works_with_no_permissions_seeded(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertDatabaseCount('role_permissions', 0);
    }

    public function test_optional_mobile_number_can_be_omitted(): void
    {
        $this->postJson(self::URL, $this->payload(['mobile_number' => null]))
            ->assertCreated()
            ->assertJsonPath('data.admin.mobile_number', null);

        $this->assertNull(AdminUser::firstOrFail()->mobile_number);
    }

    public function test_input_is_trimmed_and_email_lowercased(): void
    {
        $this->postJson(self::URL, $this->payload([
            'full_name'     => '  Ada Lovelace  ',
            'email'         => '  Ada@Example.COM ',
            'mobile_number' => ' 9876543210 ',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.admin.full_name', 'Ada Lovelace')
            ->assertJsonPath('data.admin.email', 'ada@example.com')
            ->assertJsonPath('data.admin.mobile_number', '9876543210');
    }

    public function test_password_whitespace_is_preserved(): void
    {
        $password = '  spaced pass 1  ';

        $this->postJson(self::URL, $this->payload([
            'password'              => $password,
            'password_confirmation' => $password,
        ]))->assertCreated();

        $this->assertTrue(Hash::check($password, AdminUser::firstOrFail()->password_hash));
    }

    public function test_registration_closes_after_the_first_admin(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->postJson(self::URL, $this->payload(['email' => 'second@example.com']))
            ->assertForbidden()
            ->assertExactJson([
                'success' => false,
                'message' => self::CLOSED_MESSAGE,
                'data'    => null,
            ]);

        $this->assertSame(1, AdminUser::count());
    }

    public function test_registration_is_closed_when_an_admin_already_exists(): void
    {
        $this->makeAdmin();

        $this->postJson(self::URL, $this->payload())
            ->assertForbidden()
            ->assertJsonPath('message', self::CLOSED_MESSAGE);

        $this->assertSame(1, AdminUser::count());
    }

    public function test_registration_is_closed_even_if_the_existing_admin_is_locked_or_inactive(): void
    {
        $this->makeAdmin(['status' => 'LOCKED']);

        $this->postJson(self::URL, $this->payload())->assertForbidden();

        AdminUser::query()->update(['status' => 'INACTIVE']);

        $this->postJson(self::URL, $this->payload())->assertForbidden();
    }

    public function test_closed_endpoint_does_not_reveal_registered_emails(): void
    {
        $existing = $this->makeAdmin();

        $known   = $this->postJson(self::URL, $this->payload(['email' => $existing->email]));
        $unknown = $this->postJson(self::URL, $this->payload(['email' => 'nobody@example.com']));

        $this->assertSame(403, $known->getStatusCode());
        $this->assertSame($known->getContent(), $unknown->getContent());
    }

    public function test_registration_reopens_when_only_deleted_admins_remain(): void
    {
        $deleted = $this->makeAdmin();
        $deleted->delete();

        $response = $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertNotSame($deleted->admin_code, $response->json('data.admin.admin_code'));
    }

    public function test_email_of_a_deleted_admin_cannot_be_reused(): void
    {
        $deleted = $this->makeAdmin();
        $deleted->delete();

        $this->postJson(self::URL, $this->payload(['email' => $deleted->email]))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email address is already registered.');

        $this->assertSame(0, AdminUser::count());
    }

    public function test_admin_code_never_collides_with_existing_codes(): void
    {
        $deleted = $this->makeAdmin(['admin_code' => 'ADM0002']);
        $deleted->delete();

        $response = $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertNotSame('ADM0002', $response->json('data.admin.admin_code'));
    }

    public function test_setup_key_is_not_required_when_none_is_configured(): void
    {
        config(['admin.registration.setup_key' => null]);

        $this->postJson(self::URL, $this->payload())->assertCreated();
    }

    public function test_setup_key_is_required_when_configured(): void
    {
        config(['admin.registration.setup_key' => 'top-secret']);

        $this->postJson(self::URL, $this->payload())
            ->assertForbidden()
            ->assertJsonPath('message', self::CLOSED_MESSAGE);

        $this->assertSame(0, AdminUser::count());
    }

    public function test_wrong_setup_key_is_rejected_with_the_same_message(): void
    {
        config(['admin.registration.setup_key' => 'top-secret']);

        $this->postJson(self::URL, $this->payload(['setup_key' => 'guess']))
            ->assertForbidden()
            ->assertExactJson([
                'success' => false,
                'message' => self::CLOSED_MESSAGE,
                'data'    => null,
            ]);

        $this->assertSame(0, AdminUser::count());
    }

    public function test_correct_setup_key_allows_registration(): void
    {
        config(['admin.registration.setup_key' => 'top-secret']);

        $this->postJson(self::URL, $this->payload(['setup_key' => 'top-secret']))->assertCreated();

        $this->assertSame(1, AdminUser::count());
    }

    public function test_setup_key_is_never_stored_or_returned(): void
    {
        config(['admin.registration.setup_key' => 'top-secret']);

        $response = $this->postJson(self::URL, $this->payload(['setup_key' => 'top-secret']))->assertCreated();

        $this->assertStringNotContainsString('top-secret', $response->getContent());
    }

    public function test_full_name_is_required(): void
    {
        $this->postJson(self::URL, $this->payload(['full_name' => null]))
            ->assertStatus(422)
            ->assertJsonPath('errors.full_name.0', 'Full name is required.');
    }

    public function test_blank_full_name_is_rejected(): void
    {
        $this->postJson(self::URL, $this->payload(['full_name' => '   ']))
            ->assertStatus(422)
            ->assertJsonPath('errors.full_name.0', 'Full name is required.');
    }

    public function test_full_name_length_limits(): void
    {
        $this->postJson(self::URL, $this->payload(['full_name' => 'A']))
            ->assertStatus(422)
            ->assertJsonPath('errors.full_name.0', 'Full name must be at least 2 characters.');

        $this->postJson(self::URL, $this->payload(['full_name' => str_repeat('a', 151)]))
            ->assertStatus(422)
            ->assertJsonPath('errors.full_name.0', 'Full name may not exceed 150 characters.');
    }

    public function test_email_is_required(): void
    {
        $this->postJson(self::URL, $this->payload(['email' => null]))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email address is required.');
    }

    public function test_email_must_be_valid(): void
    {
        $this->postJson(self::URL, $this->payload(['email' => 'not-an-email']))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Please enter a valid email address.');
    }

    public function test_email_cannot_exceed_150_characters(): void
    {
        $this->postJson(self::URL, $this->payload(['email' => str_repeat('a', 142) . '@test.com']))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email address may not exceed 150 characters.');
    }

    public function test_mobile_number_must_be_digits_only(): void
    {
        foreach (['98765abc10', '+919876543210', '12345', str_repeat('1', 21)] as $bad) {
            $this->postJson(self::URL, $this->payload(['mobile_number' => $bad]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['mobile_number']);
        }
    }

    public function test_password_is_required(): void
    {
        $this->postJson(self::URL, $this->payload(['password' => null, 'password_confirmation' => null]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password is required.');
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $this->postJson(self::URL, $this->payload(['password' => 'short12', 'password_confirmation' => 'short12']))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password must be at least 8 characters.');
    }

    public function test_password_cannot_exceed_255_characters(): void
    {
        $long = str_repeat('a', 256);

        $this->postJson(self::URL, $this->payload(['password' => $long, 'password_confirmation' => $long]))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password may not exceed 255 characters.');
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->postJson(self::URL, $this->payload(['password_confirmation' => 'Different#Pass1']))
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password confirmation does not match.');
    }

    public function test_invalid_request_creates_nothing(): void
    {
        $this->postJson(self::URL, $this->payload(['email' => 'bad']))->assertStatus(422);

        $this->assertSame(0, AdminUser::count());
        $this->assertDatabaseCount('admin_user_roles', 0);
        $this->assertSame(0, Role::count());
    }

    public function test_endpoint_is_public_and_needs_no_bearer_token(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();
    }

    public function test_endpoint_does_not_accept_get_method(): void
    {
        $this->getJson(self::URL)->assertStatus(405);
    }

    public function test_route_level_throttle_returns_429_after_five_requests(): void
    {
        config(['admin.registration.setup_key' => 'top-secret']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::URL, $this->payload(['setup_key' => 'guess' . $i]))->assertForbidden();
        }

        $this->postJson(self::URL, $this->payload(['setup_key' => 'top-secret']))->assertStatus(429);
    }
}
