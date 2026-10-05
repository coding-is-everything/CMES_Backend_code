<?php
namespace App\Http\Middleware;

use App\Models\CustomerSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | Get Authorization Header
        |--------------------------------------------------------------------------
        */

        $authorizationHeader = $request->header('Authorization');

        if (
            empty($authorizationHeader) ||
            ! str_starts_with($authorizationHeader, 'Bearer ')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token is required.',
                'data'    => null,
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Extract Bearer Token
        |--------------------------------------------------------------------------
        */

        $token = trim(
            substr($authorizationHeader, 7)
        );

        if ($token === '') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token.',
                'data'    => null,
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Hash Token
        |--------------------------------------------------------------------------
        |
        | Raw access token is never stored in database.
        |
        */

        $tokenHash = hash(
            'sha256',
            $token
        );

        /*
        |--------------------------------------------------------------------------
        | Find Active Customer Session
        |--------------------------------------------------------------------------
        */

        $session = CustomerSession::query()
            ->where('session_token_hash', $tokenHash)
            ->where('status', 'ACTIVE')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired authentication token.',
                'data'    => null,
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Last Activity
        |--------------------------------------------------------------------------
        */

        $session->update([
            'last_activity_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Attach Customer Information To Request
        |--------------------------------------------------------------------------
        */

        $request->attributes->set(
            'customer_account_id',
            $session->customer_account_id
        );

        $request->attributes->set(
            'customer_session_id',
            $session->id
        );

        return $next($request);
    }
}
