<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\Admin\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('customer.auth')->group(function () {
        Route::get(
            '/account',
            [AccountController::class, 'show']
        )->name('account.show');

        Route::patch(
            '/account',
            [AccountController::class, 'update']
        )->name('account.update');
    });
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'data'    => $request->user(),
    ]);
});

Route::prefix('v1/admin')
    ->group(function () {
        Route::post(
            '/auth/login',
            [AdminAuthController::class, 'login']
        )->middleware('throttle:admin-login')
            ->name('admin.auth.login');

        Route::post(
            '/auth/refresh',
            [AdminAuthController::class, 'refresh']
        )->middleware('throttle:admin-refresh')
            ->name('admin.auth.refresh');

        Route::post(
            '/auth/forgot-password',
            [AdminAuthController::class, 'forgotPassword']
        )->middleware('throttle:admin-forgot-password')
            ->name('admin.auth.forgot-password');

        Route::post(
            '/auth/reset-password',
            [AdminAuthController::class, 'resetPassword']
        )->middleware('throttle:admin-reset-password')
            ->name('admin.auth.reset-password');

        Route::post(
            '/auth/change-password',
            [AdminAuthController::class, 'changePassword']
        )->middleware(['auth:sanctum', 'throttle:admin-change-password'])
            ->name('admin.auth.change-password');

        Route::get(
            '/auth/sessions',
            [AdminAuthController::class, 'sessions']
        )->middleware('auth:sanctum')
            ->name('admin.auth.sessions');

        Route::delete(
            '/auth/sessions/{sessionId}',
            [AdminAuthController::class, 'revokeSession']
        )->whereUuid('sessionId')
            ->middleware('auth:sanctum')
            ->name('admin.auth.sessions.revoke');

        Route::get(
            '/auth/me',
            [AdminAuthController::class, 'me']
        )->middleware('auth:sanctum')
            ->name('admin.auth.me');

        Route::post(
            '/auth/logout',
            [AdminAuthController::class, 'logout']
        )->middleware('auth:sanctum')
            ->name('admin.auth.logout');

        Route::post(
            '/auth/logout-all',
            [AdminAuthController::class, 'logoutAll']
        )->middleware('auth:sanctum')
            ->name('admin.auth.logout-all');
    });

Route::prefix('v1/admin')
    ->middleware([
        'admin.auth',
        'admin.permission:admin_users.view',
    ])
    ->group(function () {
        Route::get(
            '/admin-users',
            [AdminUserController::class, 'index']
        )->name('admin.admin-users.index');
    });
