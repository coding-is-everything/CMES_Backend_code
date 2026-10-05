<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
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
