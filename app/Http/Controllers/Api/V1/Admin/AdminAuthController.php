<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminForgotPasswordRequest;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Requests\Admin\AdminRefreshTokenRequest;
use App\Http\Requests\Admin\AdminResetPasswordRequest;
use App\Http\Resources\Admin\AdminAuthResource;
use App\Models\AdminUser;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminAuthController extends Controller
{
    private const UNAUTHENTICATED_MESSAGE = 'Unauthenticated admin session.';

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
                        $result['access_token_expires_at']?->toISOString(),

                    'refresh_token_expires_at' =>
                        $result['refresh_token_expires_at']?->toISOString(),
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
                        $result['access_token_expires_at']?->toISOString(),

                    'refresh_token_expires_at' =>
                        $result['refresh_token_expires_at']?->toISOString(),
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

    /**
     * ADM-AUTH-006
     *
     * Request a password reset link. The response is identical whether or
     * not the e-mail belongs to an admin, to prevent account enumeration.
     */
    public function forgotPassword(
        AdminForgotPasswordRequest $request
    ): JsonResponse {
        try {
            $this->adminAuthService->requestPasswordReset(
                email: $request->string('email')->toString(),
                ipAddress: $request->ip()
            );
        } catch (Throwable $exception) {
            // Mail/DB failures are logged but never revealed to the caller.
            report($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'If the email address belongs to an administrator account, a password reset link has been sent.',
            'data'    => null,
        ], 200);
    }

    /**
     * ADM-AUTH-007
     *
     * Set a new password using the token from the reset email.
     */
    public function resetPassword(
        AdminResetPasswordRequest $request
    ): JsonResponse {
        try {
            $this->adminAuthService->resetPassword(
                email: $request->string('email')->toString(),
                token: $request->string('token')->toString(),
                password: $request->input('password')
            );

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. Please log in with your new password.',
                'data'    => null,
            ], 200);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to reset admin password.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * ADM-AUTH-005
     *
     * Current admin with roles and effective permissions.
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            if (! $admin instanceof AdminUser) {
                return response()->json([
                    'success' => false,
                    'message' => self::UNAUTHENTICATED_MESSAGE,
                    'data'    => null,
                ], 401);
            }

            if (! $admin->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrator account is not active.',
                    'data'    => null,
                ], 403);
            }

            $admin->load('roles:id,role_name,role_code');

            return response()->json([
                'success' => true,
                'message' => 'Admin profile retrieved successfully.',
                'data'    => [
                    'admin'       => new AdminAuthResource($admin),
                    'permissions' => $admin->permissions()
                        ->map(fn ($permission) => [
                            'permission_code' => $permission->permission_code,
                            'permission_name' => $permission->permission_name,
                            'module_name'     => $permission->module_name,
                        ])
                        ->values(),
                ],
            ], 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve admin profile.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Logout Current Admin Session
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            /**
             * Ensure this endpoint is being used
             * with an authenticated AdminUser.
             */
            $admin = $request->user();

            if (! $admin instanceof AdminUser) {
                return response()->json([
                    'success' => false,
                    'message' => self::UNAUTHENTICATED_MESSAGE,
                    'data'    => null,
                ], 401);
            }

            $this->adminAuthService->logoutCurrentSession();

            return response()->json([
                'success' => true,
                'message' => 'Admin logout successful.',
                'data'    => null,
            ], 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to logout admin session.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * ADM-AUTH-004
     *
     * Logout the admin from every device.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            if (! $admin instanceof AdminUser) {
                return response()->json([
                    'success' => false,
                    'message' => self::UNAUTHENTICATED_MESSAGE,
                    'data'    => null,
                ], 401);
            }

            $revoked = $this->adminAuthService->logoutAllSessions($admin);

            return response()->json([
                'success' => true,
                'message' => 'All admin sessions revoked successfully.',
                'data'    => [
                    'revoked_tokens' => $revoked,
                ],
            ], 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to revoke admin sessions.',
                'data'    => null,
            ], 500);
        }
    }
}
