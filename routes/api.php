<?php

use App\Http\Controllers\Api\V1\AccountController;
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
