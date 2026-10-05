<?php
namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    /**
     * Authenticate administrator and issue API token.
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

        /**
         * Always use a generic credential error.
         * Do not reveal whether an email exists.
         */
        if (! $admin || ! Hash::check($password, $admin->password_hash)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => [
                    'Invalid email or password.',
                ],
            ]);
        }

        /**
         * Account status validation.
         */
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

        /**
         * Successful Login.
         */
        RateLimiter::clear($throttleKey);

        $admin->forceFill([
            'last_login_at' => now(),
        ])->save();

        /**
         * Remove previous token with the same device name.
         *
         * This prevents unlimited duplicate token for the
         * same browser/device.
         */
        if ($deviceName) {
            $admin->tokens()
                ->where('name', $deviceName)
                ->delete();
        }

        /**
         * Token abilities.
         *
         * RBAC remains the primary authorization mechanism.
         * The token is primarily identifying the admin session.
         */
        $token = $admin->createToken(
            $deviceName ?: 'admin-web',
            ['admin']
        );

        return [
            'admin' => $admin,
            'token' => $token->plainTextToken,
        ];
    }
}
