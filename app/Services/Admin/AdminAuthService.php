<?php
namespace App\Services\Admin;

use App\Mail\AdminPasswordResetMail;
use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthService
{
    /**
     * Authenticate administrator and issue access + refresh tokens.
     */
    public function login(
        string $email,
        string $password,
        ?string $deviceName = null
    ): array {
        $email = strtolower(trim($email));

        $throttleKey = 'admin-login:' . strtolower($email);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [
                    "Too many login attempts. Please try again in {$seconds} seconds.",
                ],
            ]);
        }

        $admin = AdminUser::query()
            ->with([
                'roles:id,role_name,role_code',
            ])
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();

        if (
            ! $admin ||
            ! Hash::check($password, $admin->password_hash)
        ) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => [
                    'Invalid email or password.',
                ],
            ]);
        }

        if ($admin->status === 'LOCKED') {
            throw ValidationException::withMessages([
                'email' => [
                    'Your administrator account is locked.',
                ],
            ]);
        }

        if ($admin->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'email' => [
                    'Your administrator account is inactive.',
                ],
            ]);
        }

        RateLimiter::clear($throttleKey);

        return DB::transaction(function () use (
            $admin,
            $deviceName
        ) {

            $admin->forceFill([
                'last_login_at' => now(),
            ])->save();

            /*
             * Unique identifier for this login session.
             *
             * Both access and refresh tokens belonging to
             * this session use this identifier.
             */
            $sessionId = (string) Str::uuid();

            $device = $deviceName ?: 'admin-web';

            /*
             * Remove any existing token pair for the same
             * device name.
             */
            $escapedDevice = addcslashes($device, '%_\\');

            $admin->tokens()
                ->where(function ($query) use ($escapedDevice) {
                    $query->where('name', 'like', 'admin-access:' . $escapedDevice . ':%')
                        ->orWhere('name', 'like', 'admin-refresh:' . $escapedDevice . ':%');
                })
                ->delete();

            /*
             * Create short-lived access token.
             */
            $accessToken = $admin->createToken(
                'admin-access:' . $device,
                [
                    config(
                        'admin.auth.access_token_ability',
                        'admin:access'
                    ),
                ],
                now()->addMinutes(
                    config(
                        'admin.auth.access_token_ttl',
                        60
                    )
                )
            );

            /*
             * Create long-lived refresh token.
             */
            $refreshToken = $admin->createToken(
                'admin-refresh:' . $device,
                [
                    config(
                        'admin.auth.refresh_token_ability',
                        'admin:refresh'
                    ),
                ],
                now()->addMinutes(
                    config(
                        'admin.auth.refresh_token_ttl',
                        43200
                    )
                )
            );

            /*
             * Store session identifier in token names.
             *
             * We cannot put the session identifier into the
             * plaintext token itself.
             */
            $accessToken->accessToken->forceFill([
                'name' => 'admin-access:' . $device . ':' . $sessionId,
            ])->save();

            $refreshToken->accessToken->forceFill([
                'name' => 'admin-refresh:' . $device . ':' . $sessionId,
            ])->save();

            return [
                'admin'                    => $admin->fresh([
                    'roles:id,role_name,role_code',
                ]),

                'access_token'             => $accessToken->plainTextToken,

                'refresh_token'            => $refreshToken->plainTextToken,

                'access_token_expires_at'  =>
                $accessToken->accessToken->expires_at,

                'refresh_token_expires_at' =>
                $refreshToken->accessToken->expires_at,
            ];
        });
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refresh(
        string $refreshToken,
        ?string $deviceName = null
    ): array {
        /*
         * Sanctum hashes incoming token and locates the
         * corresponding PersonalAccessToken record.
         */
        $token = PersonalAccessToken::findToken(
            $refreshToken
        );

        if (! $token) {
            throw ValidationException::withMessages([
                'refresh_token' => [
                    'Invalid or expired refresh token.',
                ],
            ]);
        }

        /*
         * Ensure this is actually a refresh token.
         */
        if (
            ! $token->can(
                config(
                    'admin.auth.refresh_token_ability',
                    'admin:refresh'
                )
            )
        ) {
            throw ValidationException::withMessages([
                'refresh_token' => [
                    'Invalid refresh token.',
                ],
            ]);
        }

        /*
         * Explicit expiration check.
         */
        if (
            $token->expires_at &&
            $token->expires_at->isPast()
        ) {
            $token->delete();

            throw ValidationException::withMessages([
                'refresh_token' => [
                    'Refresh token has expired.',
                ],
            ]);
        }

        /** @var AdminUser|null $admin */
        $admin = $token->tokenable;

        if (! $admin) {
            $token->delete();

            throw ValidationException::withMessages([
                'refresh_token' => [
                    'Invalid refresh token.',
                ],
            ]);
        }

        /*
         * Admin account must still be active.
         */
        if (! $admin->isActive()) {
            $token->delete();

            throw ValidationException::withMessages([
                'refresh_token' => [
                    'Administrator account is not active.',
                ],
            ]);
        }

        /*
         * Extract session identifier from token name.
         *
         * Example:
         *
         * admin-refresh:Chrome:550e8400-e29b-41d4-a716-446655440000
         */
        $sessionId = $this->extractSessionId(
            $token->name
        );

        if (! $sessionId) {
            $token->delete();

            throw ValidationException::withMessages([
                'refresh_token' => [
                    'Invalid refresh token session.',
                ],
            ]);
        }

        return DB::transaction(function () use (
            $admin,
            $token,
            $sessionId,
            $deviceName
        ) {

            /*
             * Revoke current refresh token.
             *
             * This gives us refresh-token rotation.
             */
            $token->delete();

            /*
             * Revoke the previous access token belonging
             * to this session.
             */
            $admin->tokens()
                ->where(
                    'name',
                    'like',
                    'admin-access:%:' . $sessionId
                )
                ->delete();

            $device = $deviceName
                ?: $this->extractDeviceName($token->name);

            /*
             * Create new access token.
             */
            $newAccessToken = $admin->createToken(
                'admin-access:' . $device . ':' . $sessionId,
                [
                    config(
                        'admin.auth.access_token_ability',
                        'admin:access'
                    ),
                ],
                now()->addMinutes(
                    config(
                        'admin.auth.access_token_ttl',
                        60
                    )
                )
            );

            /*
             * Create new refresh token.
             */
            $newRefreshToken = $admin->createToken(
                'admin-refresh:' . $device . ':' . $sessionId,
                [
                    config(
                        'admin.auth.refresh_token_ability',
                        'admin:refresh'
                    ),
                ],
                now()->addMinutes(
                    config(
                        'admin.auth.refresh_token_ttl',
                        43200
                    )
                )
            );

            return [
                'admin'                    => $admin->fresh([
                    'roles:id,role_name,role_code',
                ]),

                'access_token'             =>
                $newAccessToken->plainTextToken,

                'refresh_token'            =>
                $newRefreshToken->plainTextToken,

                'access_token_expires_at'  =>
                $newAccessToken->accessToken->expires_at,

                'refresh_token_expires_at' =>
                $newRefreshToken->accessToken->expires_at,
            ];
        });
    }

    /**
     * Extract session UUID from token name.
     */
    protected function extractSessionId(
        string $tokenName
    ): ?string {
        $parts = explode(':', $tokenName);

        if (count($parts) < 3) {
            return null;
        }

        return end($parts);
    }

    /**
     * Extract device name from token name.
     */
    protected function extractDeviceName(
        string $tokenName
    ): string {
        $parts = explode(':', $tokenName);

        if (count($parts) < 3) {
            return 'admin-web';
        }

        // Drop the "admin-refresh" / "admin-access" prefix and the session id.
        array_shift($parts);
        array_pop($parts);

        return implode(':', $parts) ?: 'admin-web';
    }

    /**
     * Logout the current admin session.
     *
     * Revokes:
     * 1. Current access token
     * 2. Matching refresh token for the same session
     *
     * Other admin, sessions remain active.
     */
    public function logoutCurrentSession(): void
    {
        $currentToken = request()->user()?->currentAccessToken();

        if (! $currentToken) {
            return;
        }

        $sessionId = $this->extractSessionId($currentToken->name);

        DB::transaction(function () use ($currentToken, $sessionId) {
            //Revoke current access token.
            $currentToken->delete();

            //If the token follows our admin session naming convention,
            //Revoke the corresponding refresh token as well.
            if ($sessionId) {
                PersonalAccessToken::query()
                    ->where('tokenable_type', AdminUser::class)
                    ->where('name', 'like', 'admin-refresh:%' . $sessionId)
                    ->delete();
            }
        });
    }

    /**
     * Whether first-administrator registration is still available.
     *
     * Open only while no (non-deleted) administrator exists.
     */
    public function registrationOpen(): bool
    {
        return ! AdminUser::query()->exists();
    }

    /**
     * Register the very first administrator.
     *
     * Allowed only while no administrator exists, and only with the
     * configured setup key (when one is configured). The new admin is
     * ACTIVE and receives the SUPER_ADMIN role with every permission.
     * No tokens are issued - the admin signs in through the normal login.
     *
     * @param array{full_name: string, email: string, password: string, mobile_number?: ?string} $data
     *
     * @throws AuthorizationException when registration is closed or the key is wrong
     * @throws ValidationException    when the email belonged to a deleted admin
     * @throws LockTimeoutException   when another registration is in flight
     */
    public function registerFirstAdmin(array $data, ?string $setupKey = null): AdminUser
    {
        // Serialises concurrent attempts: an empty table gives row locks nothing to hold.
        return Cache::lock('admin-first-registration', 10)->block(5, function () use ($data, $setupKey) {
            $configuredKey = config('admin.registration.setup_key');

            if (
                ! $this->registrationOpen() ||
                ($configuredKey && ! hash_equals((string) $configuredKey, (string) $setupKey))
            ) {
                throw new AuthorizationException(
                    'Administrator registration is not available.'
                );
            }

            $email = strtolower(trim($data['email']));

            if (AdminUser::withTrashed()->where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => [
                        'Email address is already registered.',
                    ],
                ]);
            }

            return DB::transaction(function () use ($data, $email) {
                $admin = AdminUser::create([
                    'admin_code'    => $this->nextAdminCode(),
                    'full_name'     => $data['full_name'],
                    'email'         => $email,
                    'mobile_number' => $data['mobile_number'] ?? null,
                    'password_hash' => Hash::make($data['password']),
                    'status'        => 'ACTIVE',
                ]);

                $roleCode = config('admin.registration.role_code', 'SUPER_ADMIN');

                $role = Role::query()->firstOrCreate(
                    ['role_code' => $roleCode],
                    [
                        'role_name'   => 'Super Administrator',
                        'description' => 'Full system administration',
                    ]
                );

                DB::table('admin_user_roles')->insert([
                    'admin_user_id' => $admin->id,
                    'role_id'       => $role->id,
                ]);

                $missing = DB::table('permissions')
                    ->whereNotIn('id', function ($query) use ($role) {
                        $query->select('permission_id')
                            ->from('role_permissions')
                            ->where('role_id', $role->id);
                    })
                    ->pluck('id')
                    ->map(fn ($permissionId) => [
                        'role_id'       => $role->id,
                        'permission_id' => $permissionId,
                    ])
                    ->all();

                if ($missing) {
                    DB::table('role_permissions')->insert($missing);
                }

                return $admin->load('roles:id,role_name,role_code');
            });
        });
    }

    /**
     * Next free admin code, e.g. ADM0001.
     */
    protected function nextAdminCode(): string
    {
        $next = ((int) AdminUser::withTrashed()->max('id')) + 1;

        do {
            $code = 'ADM' . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (AdminUser::withTrashed()->where('admin_code', $code)->exists());

        return $code;
    }

    /**
     * Start a password reset for the given e-mail address.
     *
     * Deliberately returns nothing and never throws for unknown, locked,
     * inactive or deleted accounts, so callers cannot enumerate admins.
     */
    public function requestPasswordReset(
        string $email,
        ?string $ipAddress = null
    ): void {
        $admin = AdminUser::query()
            ->where('email', strtolower(trim($email)))
            ->whereNull('deleted_at')
            ->first();

        if (! $admin || ! $admin->isActive()) {
            return;
        }

        $cooldown = (int) config('admin.password_reset.cooldown', 60);

        $recentlyRequested = DB::table('admin_password_resets')
            ->where('admin_user_id', $admin->id)
            ->whereNull('used_at')
            ->where('created_at', '>', now()->subSeconds($cooldown))
            ->exists();

        if ($recentlyRequested) {
            return;
        }

        $ttl      = (int) config('admin.password_reset.ttl', 60);
        $rawToken = Str::random(64);

        DB::transaction(function () use ($admin, $rawToken, $ttl, $ipAddress) {
            // Only the newest link stays valid.
            DB::table('admin_password_resets')
                ->where('admin_user_id', $admin->id)
                ->whereNull('used_at')
                ->delete();

            DB::table('admin_password_resets')->insert([
                'admin_user_id' => $admin->id,
                'token_hash'    => hash('sha256', $rawToken),
                'ip_address'    => $ipAddress,
                'expires_at'    => now()->addMinutes($ttl),
                'created_at'    => now(),
            ]);
        });

        $resetUrl = rtrim((string) config('admin.password_reset.frontend_url'), '/')
            . config('admin.password_reset.path', '/reset-password')
            . '?' . http_build_query([
                'token' => $rawToken,
                'email' => $admin->email,
            ]);

        Mail::to($admin->email)->send(
            new AdminPasswordResetMail($admin, $resetUrl, $ttl)
        );
    }

    /**
     * Complete a password reset using the emailed token.
     *
     * Every failure mode (unknown email, wrong token, used, expired,
     * inactive account) yields the same message so nothing is leaked.
     *
     * On success the token is consumed, all other reset links are
     * invalidated and every existing admin session is revoked.
     *
     * @throws ValidationException
     */
    public function resetPassword(
        string $email,
        string $token,
        string $password
    ): void {
        $email = strtolower(trim($email));

        $record = DB::table('admin_password_resets')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        $admin = $record
            ? AdminUser::query()
                ->whereKey($record->admin_user_id)
                ->where('email', $email)
                ->whereNull('deleted_at')
                ->first()
            : null;

        if (
            ! $record ||
            ! $admin ||
            $record->used_at !== null ||
            Carbon::parse($record->expires_at)->isPast() ||
            ! $admin->isActive()
        ) {
            throw ValidationException::withMessages([
                'token' => [
                    'Invalid or expired password reset token.',
                ],
            ]);
        }

        DB::transaction(function () use ($admin, $record, $password) {
            $admin->forceFill([
                'password_hash' => Hash::make($password),
            ])->save();

            DB::table('admin_password_resets')
                ->where('id', $record->id)
                ->update(['used_at' => now()]);

            // Any other outstanding links for this admin are now void.
            DB::table('admin_password_resets')
                ->where('admin_user_id', $admin->id)
                ->whereNull('used_at')
                ->delete();

            // Force re-login everywhere with the new password.
            $admin->tokens()->delete();
        });

        // Clear failed-login lockout so the admin can sign in right away.
        RateLimiter::clear('admin-login:' . $email);
    }

    /**
     * List the admin's active login sessions (one per device).
     *
     * A session is the access + refresh token pair that share the UUID at
     * the end of their names. Sessions whose tokens have all expired, and
     * tokens not following the admin naming convention, are left out.
     *
     * @return array<int, array<string, mixed>> current session first, then
     *                                          most recently used.
     */
    public function listSessions(AdminUser $admin): array
    {
        $currentToken     = $admin->currentAccessToken();
        $currentSessionId = $currentToken
            ? $this->extractSessionId($currentToken->name)
            : null;

        $sessions = [];

        foreach ($admin->tokens()->get() as $token) {
            $isAccess  = str_starts_with($token->name, 'admin-access:');
            $isRefresh = str_starts_with($token->name, 'admin-refresh:');

            $sessionId = $this->extractSessionId($token->name);

            if ((! $isAccess && ! $isRefresh) || ! $sessionId) {
                continue;
            }

            $sessions[$sessionId] ??= [
                'session_id'               => $sessionId,
                'device_name'              => $this->extractDeviceName($token->name),
                'is_current'               => $sessionId === $currentSessionId,
                'issued_at'                => $token->created_at,
                'last_used_at'             => null,
                'access_token_expires_at'  => null,
                'refresh_token_expires_at' => null,
            ];

            $session = &$sessions[$sessionId];

            if ($token->created_at && $token->created_at->gt($session['issued_at'])) {
                $session['issued_at'] = $token->created_at;
            }

            if (
                $token->last_used_at &&
                (! $session['last_used_at'] || $token->last_used_at->gt($session['last_used_at']))
            ) {
                $session['last_used_at'] = $token->last_used_at;
            }

            $session[$isAccess ? 'access_token_expires_at' : 'refresh_token_expires_at']
                = $token->expires_at;

            unset($session);
        }

        $active = array_filter($sessions, $this->isSessionActive(...));

        usort($active, $this->compareSessions(...));

        return array_map(fn (array $session) => [
            'session_id'               => $session['session_id'],
            'device_name'              => $session['device_name'],
            'is_current'               => $session['is_current'],
            'issued_at'                => $session['issued_at']?->toISOString(),
            'last_used_at'             => $session['last_used_at']?->toISOString(),
            'access_token_expires_at'  => $session['access_token_expires_at']?->toISOString(),
            'refresh_token_expires_at' => $session['refresh_token_expires_at']?->toISOString(),
        ], $active);
    }

    /**
     * A session is active while at least one of its tokens is unexpired.
     *
     * @param array<string, mixed> $session
     */
    protected function isSessionActive(array $session): bool
    {
        foreach (['access_token_expires_at', 'refresh_token_expires_at'] as $key) {
            if ($session[$key]?->isFuture()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Current session first, then most recently used, then most recently issued.
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    protected function compareSessions(array $a, array $b): int
    {
        if ($a['is_current'] !== $b['is_current']) {
            return $a['is_current'] ? -1 : 1;
        }

        $aUsed = $a['last_used_at']?->getTimestamp() ?? 0;
        $bUsed = $b['last_used_at']?->getTimestamp() ?? 0;

        return $bUsed <=> $aUsed
            ?: $b['issued_at']->getTimestamp() <=> $a['issued_at']->getTimestamp();
    }

    /**
     * Revoke one of the admin's sessions (access + refresh token pair).
     *
     * Only tokens that belong to this admin and follow the admin naming
     * convention can match, so another admin's session id is simply "not found".
     *
     * @return bool false when the admin has no such session.
     */
    public function revokeSession(AdminUser $admin, string $sessionId): bool
    {
        $revoked = $admin->tokens()
            ->where(function ($query) use ($sessionId) {
                $query->where('name', 'like', 'admin-access:%:' . $sessionId)
                    ->orWhere('name', 'like', 'admin-refresh:%:' . $sessionId);
            })
            ->delete();

        return $revoked > 0;
    }

    /**
     * Whether the given session id belongs to the token making the request.
     */
    public function isCurrentSession(AdminUser $admin, string $sessionId): bool
    {
        $currentToken = $admin->currentAccessToken();

        return $currentToken
            && $this->extractSessionId($currentToken->name) === $sessionId;
    }

    /**
     * Change the password of a signed-in admin.
     *
     * The session making the call stays signed in; every other session
     * (other devices) is revoked, as are outstanding reset links.
     *
     * @throws ValidationException when the current password is wrong
     */
    public function changePassword(
        AdminUser $admin,
        string $currentPassword,
        string $newPassword
    ): void {
        if (! Hash::check($currentPassword, $admin->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'Current password is incorrect.',
                ],
            ]);
        }

        $currentToken = $admin->currentAccessToken();
        $sessionId    = $currentToken
            ? $this->extractSessionId($currentToken->name)
            : null;

        DB::transaction(function () use ($admin, $newPassword, $currentToken, $sessionId) {
            $admin->forceFill([
                'password_hash' => Hash::make($newPassword),
            ])->save();

            $others = $admin->tokens();

            if ($sessionId) {
                // Keep this device's access + refresh pair.
                $others->where('name', 'not like', '%:' . $sessionId);
            } elseif ($currentToken instanceof PersonalAccessToken) {
                $others->whereKeyNot($currentToken->getKey());
            }

            $others->delete();

            DB::table('admin_password_resets')
                ->where('admin_user_id', $admin->id)
                ->whereNull('used_at')
                ->delete();
        });

        RateLimiter::clear('admin-login:' . strtolower($admin->email));
    }

    /**
     * Logout every session of the admin.
     *
     * Revokes all access and refresh tokens on every device.
     *
     * @return int Number of tokens revoked.
     */
    public function logoutAllSessions(AdminUser $admin): int
    {
        return $admin->tokens()->delete();
    }
}
