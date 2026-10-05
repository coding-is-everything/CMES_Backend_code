<?php
namespace App\Http\Middleware;

use App\Models\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $token = $request->bearerToken();

        if (empty($token)) {
            return $this->unauthorized('Authentication token is required.');
        }

        // Raw access token is never stored in database.
        $session = AdminSession::query()
            ->with('adminUser')
            ->where('session_token_hash', hash('sha256', $token))
            ->where('status', 'ACTIVE')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        $admin = $session?->adminUser;

        if (! $admin || ! $admin->isActive()) {
            return $this->unauthorized('Invalid or expired authentication token.');
        }

        $session->update([
            'last_activity_at' => now(),
        ]);

        $request->attributes->set('admin_user', $admin);
        $request->attributes->set('admin_user_id', $admin->id);
        $request->attributes->set('admin_session_id', $session->id);

        return $next($request);
    }

    protected function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ], 401);
    }
}
