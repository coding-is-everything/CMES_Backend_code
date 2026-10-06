<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Requests\Admin\AdminRefreshTokenRequest;
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
     */
    public function login(
        AdminLoginRequest $request
    ): JsonResponse {
        try {

            $result = $this->adminAuthService->login(
                email: $request
                    ->string('email')
                    ->toString(),

                password: $request
                    ->string('password')
                    ->toString(),

                deviceName: $request->input('device_name')
            );

            return response()->json([
                'success' => true,

                'message' => 'Admin login successful.',

                'data'    => [
                    'admin'                    => new AdminAuthResource(
                        $result['admin']
                    ),

                    'access_token'             =>
                    $result['access_token'],

                    'refresh_token'            =>
                    $result['refresh_token'],

                    'token_type'               => 'Bearer',

                    'access_token_expires_at'  =>
                        $result['access_token_expires_at']
                            ?->toISOString(),

                    'refresh_token_expires_at' =>
                        $result['refresh_token_expires_at']
                            ?->toISOString(),
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

    /**
     * ADM-AUTH-002
     */
    public function refresh(
        AdminRefreshTokenRequest $request
    ): JsonResponse {
        try {

            $result = $this->adminAuthService->refresh(
                refreshToken: $request
                    ->string('refresh_token')
                    ->toString(),

                deviceName: $request->input('device_name')
            );

            return response()->json([
                'success' => true,

                'message' =>
                'Admin access token refreshed successfully.',

                'data'    => [
                    'admin'                    => new AdminAuthResource(
                        $result['admin']
                    ),

                    'access_token'             =>
                    $result['access_token'],

                    'refresh_token'            =>
                    $result['refresh_token'],

                    'token_type'               => 'Bearer',

                    'access_token_expires_at'  =>
                        $result['access_token_expires_at']
                            ?->toISOString(),

                    'refresh_token_expires_at' =>
                        $result['refresh_token_expires_at']
                            ?->toISOString(),
                ],
            ], 200);

        } catch (ValidationException $exception) {

            throw $exception;

        } catch (Throwable $exception) {

            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                'Unable to refresh admin access token.',
                'data'    => null,
            ], 500);
        }
    }
}
