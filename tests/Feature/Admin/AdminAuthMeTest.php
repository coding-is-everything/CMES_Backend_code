<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthMeTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/admin/auth/login';

    private const URL = '/api/v1/admin/auth/me';

    private const PASSWORD = 'Secret@12345';

    private function makeAdmin(array $overrides = []): AdminUser
    {
        static $counter = 0;
        $counter++;

        return AdminUser::create(array_merge([
            'admin_code'    => 'MEA' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'full_name'     => 'Me Admin',
            'email'         => "me{$counter}@example.com",
            'mobile_number' => '9999999999',
            'password_hash' => Hash::make(self::PASSWORD),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    private function accessTokenFor(AdminUser $admin): string
    {
        return $this->postJson(self::LOGIN_URL, [
            'email'    => $admin->email,
            'password' => self::PASSWORD,
        ])->assertOk()->json('data.access_token');
    }

    private function makeRole(string $code, string $name): Role
    {
        return Role::create(['role_code' => $code, 'role_name' => $name]);
    }

    private function makePermission(string $code, string $name, string $module): Permission
    {
        return Permission::create([
            'permission_code' => $code,
            'permission_name' => $name,
            'module_name'     => $module,
        ]);
    }

    private function assignRole(AdminUser $admin, Role $role): void
    {
        DB::table('admin_user_roles')->insert([
            'admin_user_id' => $admin->id,
            'role_id'       => $role->id,
        ]);
    }

    private function grant(Role $role, Permission ...$permissions): void
    {
        foreach ($permissions as $permission) {
            DB::table('role_permissions')->insert([
                'role_id'       => $role->id,
                'permission_id' => $permission->id,
            ]);
        }
    }

    /**
     * Sanctum caches the resolved user per guard; reset it so each request
     * authenticates from the bearer token again.
     */
    private function getMe(string $accessToken)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($accessToken)->getJson(self::URL);
    }

    public function test_admin_can_fetch_own_profile_with_roles_and_permissions(): void
    {
        $admin = $this->makeAdmin();
        $role  = $this->makeRole('SUPER_ADMIN', 'Super Admin');
        $view  = $this->makePermission('admin_users.view', 'View Admin Users', 'Admin Users');

        $this->assignRole($admin, $role);
        $this->grant($role, $view);

        $this->getMe($this->accessTokenFor($admin))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admin profile retrieved successfully.')
            ->assertJsonPath('data.admin.id', $admin->id)
            ->assertJsonPath('data.admin.email', $admin->email)
            ->assertJsonPath('data.admin.roles.0.role_code', 'SUPER_ADMIN')
            ->assertJsonPath('data.permissions.0.permission_code', 'admin_users.view')
            ->assertJsonPath('data.permissions.0.permission_name', 'View Admin Users')
            ->assertJsonPath('data.permissions.0.module_name', 'Admin Users')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'admin' => ['id', 'admin_code', 'full_name', 'email', 'mobile_number', 'status', 'last_login_at', 'roles'],
                    'permissions',
                ],
            ]);
    }

    public function test_response_never_exposes_password_or_tokens(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->getMe($this->accessTokenFor($admin))->assertOk();

        $this->assertArrayNotHasKey('password_hash', $response->json('data.admin'));
        $this->assertArrayNotHasKey('access_token', $response->json('data'));
        $this->assertArrayNotHasKey('refresh_token', $response->json('data'));
        $this->assertStringNotContainsString('password', strtolower($response->getContent()));
    }

    public function test_admin_without_roles_gets_empty_roles_and_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->getMe($this->accessTokenFor($admin))
            ->assertOk()
            ->assertJsonCount(0, 'data.admin.roles')
            ->assertJsonCount(0, 'data.permissions')
            ->assertJsonPath('data.permissions', []);
    }

    public function test_role_without_permissions_returns_role_but_no_permissions(): void
    {
        $admin = $this->makeAdmin();
        $this->assignRole($admin, $this->makeRole('SUPPORT', 'Support'));

        $this->getMe($this->accessTokenFor($admin))
            ->assertOk()
            ->assertJsonCount(1, 'data.admin.roles')
            ->assertJsonCount(0, 'data.permissions');
    }

    public function test_permissions_are_merged_and_deduplicated_across_roles(): void
    {
        $admin = $this->makeAdmin();
        $roleA = $this->makeRole('ROLE_A', 'Role A');
        $roleB = $this->makeRole('ROLE_B', 'Role B');

        $view   = $this->makePermission('admin_users.view', 'View Admin Users', 'Admin Users');
        $create = $this->makePermission('admin_users.create', 'Create Admin Users', 'Admin Users');

        $this->assignRole($admin, $roleA);
        $this->assignRole($admin, $roleB);
        $this->grant($roleA, $view, $create);
        $this->grant($roleB, $view);

        $response = $this->getMe($this->accessTokenFor($admin))
            ->assertOk()
            ->assertJsonCount(2, 'data.admin.roles')
            ->assertJsonCount(2, 'data.permissions');

        $this->assertEqualsCanonicalizing(
            ['admin_users.view', 'admin_users.create'],
            array_column($response->json('data.permissions'), 'permission_code')
        );
    }

    public function test_permissions_are_sorted_by_module_then_code(): void
    {
        $admin = $this->makeAdmin();
        $role  = $this->makeRole('OPS', 'Ops');

        $this->assignRole($admin, $role);
        $this->grant(
            $role,
            $this->makePermission('licenses.view', 'View Licenses', 'Licenses'),
            $this->makePermission('admin_users.view', 'View Admin Users', 'Admin Users'),
            $this->makePermission('admin_users.create', 'Create Admin Users', 'Admin Users'),
        );

        $codes = array_column(
            $this->getMe($this->accessTokenFor($admin))->assertOk()->json('data.permissions'),
            'permission_code'
        );

        $this->assertSame(['admin_users.create', 'admin_users.view', 'licenses.view'], $codes);
    }

    public function test_only_own_roles_and_permissions_are_returned(): void
    {
        $admin      = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $mine   = $this->makeRole('MINE', 'Mine');
        $theirs = $this->makeRole('THEIRS', 'Theirs');

        $this->assignRole($admin, $mine);
        $this->assignRole($otherAdmin, $theirs);
        $this->grant($mine, $this->makePermission('a.view', 'A View', 'A'));
        $this->grant($theirs, $this->makePermission('b.view', 'B View', 'B'));

        $response = $this->getMe($this->accessTokenFor($admin))->assertOk();

        $this->assertSame(['MINE'], array_column($response->json('data.admin.roles'), 'role_code'));
        $this->assertSame(['a.view'], array_column($response->json('data.permissions'), 'permission_code'));
    }

    public function test_changes_to_permissions_are_reflected_on_next_call(): void
    {
        $admin = $this->makeAdmin();
        $role  = $this->makeRole('OPS', 'Ops');
        $perm  = $this->makePermission('licenses.view', 'View Licenses', 'Licenses');

        $this->assignRole($admin, $role);
        $token = $this->accessTokenFor($admin);

        $this->getMe($token)->assertOk()->assertJsonCount(0, 'data.permissions');

        $this->grant($role, $perm);

        $this->getMe($token)->assertOk()->assertJsonCount(1, 'data.permissions');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_me_rejects_invalid_token(): void
    {
        $this->getMe('1|' . str_repeat('x', 40))->assertUnauthorized();
    }

    public function test_me_rejects_expired_access_token(): void
    {
        config(['admin.auth.access_token_ttl' => 10]);

        $token = $this->accessTokenFor($this->makeAdmin());

        $this->travel(11)->minutes();

        $this->getMe($token)->assertUnauthorized();
    }

    public function test_me_rejects_access_token_after_logout(): void
    {
        $token = $this->accessTokenFor($this->makeAdmin());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/admin/auth/logout')->assertOk();

        $this->getMe($token)->assertUnauthorized();
    }

    public function test_locked_admin_is_forbidden(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->accessTokenFor($admin);

        $admin->forceFill(['status' => 'LOCKED'])->save();

        $this->getMe($token)
            ->assertForbidden()
            ->assertExactJson([
                'success' => false,
                'message' => 'Administrator account is not active.',
                'data'    => null,
            ]);
    }

    public function test_inactive_admin_is_forbidden(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->accessTokenFor($admin);

        $admin->forceFill(['status' => 'INACTIVE'])->save();

        $this->getMe($token)->assertForbidden();
    }

    public function test_me_does_not_accept_post_method(): void
    {
        $token = $this->accessTokenFor($this->makeAdmin());

        $this->withToken($token)->postJson(self::URL)->assertStatus(405);
    }
}
