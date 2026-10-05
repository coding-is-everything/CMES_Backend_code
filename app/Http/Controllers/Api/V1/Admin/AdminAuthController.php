<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Resources\Admin\AdminAuthResource;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminAuthController extends Controller
{
    public function __construct(
        protected AdminAuthService $adminAuthService
    ) {
    }

    /**
     * ADM-AUTH-001
     *
     * Admin Login using email and password.
     */
    public function login(
        AdminLoginRequest $request
    ): JsonResponse {
        try {
            $result = $this->adminAuthService->login(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString(),
                deviceName: $request->input('device_name')
            );

            return response()->json([
                'success' => true,
                'message' => 'Admin login successful.',
                'data'    => [
                    'admin'      => new AdminAuthResource(
                        $result['admin']
                    ),
                    'token'      => $result['token'],
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process admin login.',
                'data'    => null,
            ], 500);
        }
    }
}
