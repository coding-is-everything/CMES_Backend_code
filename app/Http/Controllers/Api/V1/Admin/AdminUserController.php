<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminUsersRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdminUserController extends Controller
{
    public function __construct(
        protected AdminUserService $adminUserService
    ) {
    }

    /**
     * ADM-ADMIN-001
     *
     * GET /api/v1/admin/admin-users
     *
     * List/search admin accounts.
     */
    public function index(
        ListAdminUsersRequest $request
    ): JsonResponse {
        try {
            $admins = $this->adminUserService->listAdmins(
                $request->validated()
            );

            return response()->json([
                'success' => true,

                'message' => 'Admin users retrieved successfully.',

                'data'    => AdminUserResource::collection(
                    $admins->getCollection()
                ),

                'meta'    => [
                    'current_page' => $admins->currentPage(),

                    'per_page'     => $admins->perPage(),

                    'total'        => $admins->total(),

                    'last_page'    => $admins->lastPage(),

                    'from'         => $admins->firstItem(),

                    'to'           => $admins->lastItem(),
                ],

                'links'   => [
                    'first' => $admins->url(1),

                    'last'  => $admins->url(
                        $admins->lastPage()
                    ),

                    'prev'  => $admins->previousPageUrl(),

                    'next'  => $admins->nextPageUrl(),
                ],
            ], 200);

        } catch (Throwable $exception) {

            report($exception);

            return response()->json([
                'success' => false,

                'message' =>
                'Unable to retrieve admin users.',

                'data'    => null,
            ], 500);
        }
    }
}
