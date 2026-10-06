<?php
namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            $admin->tokens()
                ->whereIn('name', [
                    'admin-access:' . $device,
                    'admin-refresh:' . $device,
                ])
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

        array_shift($parts);
        array_shift($parts);

        array_pop($parts);

        return implode(':', $parts) ?: 'admin-web';
    }
}
