<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    /**
     * Handle an incoming request.
     *
     * Usage: admin.permission:code1,code2 (any one of the codes suffices).
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions
    ): Response {

        $adminUserId = $request->attributes->get('admin_user_id');

        if (! $adminUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token is required.',
                'data'    => null,
            ], 401);
        }

        $allowed = DB::table('admin_user_roles as aur')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aur.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aur.admin_user_id', $adminUserId)
            ->whereIn('p.permission_code', $permissions)
            ->exists();

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'data'    => null,
            ], 403);
        }

        return $next($request);
    }
}
