<?php
namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(10)
            ->by(
                strtolower(
                    (string) $request->input('email')
                ) . '|' . $request->ip()
            );
        });

        RateLimiter::for('admin-forgot-password', function (Request $request) {
            $email = $request->input('email');

            // A non-string email fails validation; don't let it break the limiter key.
            return Limit::perMinute(5)
                ->by(
                    (is_string($email) ? strtolower(trim($email)) : '')
                    . '|' . $request->ip()
                );
        });

        // Limits setup-key guessing on first-admin registration.
        RateLimiter::for('admin-register', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->ip());
        });

        // Limits current-password guessing from a stolen access token.
        RateLimiter::for('admin-change-password', function (Request $request) {
            return Limit::perMinute(5)->by(
                'admin-change-password:' . ($request->user()?->getAuthIdentifier() ?? $request->ip())
            );
        });

        // Limits token guessing; keyed by IP because the token itself is the secret.
        RateLimiter::for('admin-reset-password', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->ip());
        });

        RateLimiter::for('admin-refresh', function (Request $request) {
            return Limit::perMinute(20)
            ->by(
                $request->ip()
            );
        });
    }
}
