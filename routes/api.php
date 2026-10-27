<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PinResetApiController;
use App\Http\Controllers\Api\V1\Pos\AuthorizeController;
use App\Http\Controllers\Api\V1\Pos\OrderController;
use App\Http\Controllers\Api\V1\Pos\ShiftController;
use App\Http\Controllers\Api\V1\Pos\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * @req FR-AUTH-01, FR-AUTH-02, FR-DEV-01, FR-POS-01, FR-POS-02, FR-POS-05, FR-POS-10, FR-OFF-01, FR-OFF-04, FSD-01, FSD-06, FSD-07, FSD-08, FSD-09, FSD-10, FSD-17, BR-25
 */

Route::prefix('v1')->group(function () {
    // Public Authentication, Device Activation & Cashier PIN Reset
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('/auth/device-activate', [AuthController::class, 'activateDevice'])->middleware('throttle:10,1');
    Route::post('/auth/forgot-pin', [PinResetApiController::class, 'requestOtp'])->middleware('throttle:10,1');
    Route::post('/auth/reset-pin', [PinResetApiController::class, 'verifyAndReset'])->middleware('throttle:10,1');

    // Authenticated Internal POS API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // Orders & POS Transactions
        Route::post('/orders', [OrderController::class, 'store']);
        Route::post('/orders/{order}/void', [OrderController::class, 'void']);

        // Shift & Cash Management
        Route::get('/shifts/current', [ShiftController::class, 'current']);
        Route::post('/shifts/open', [ShiftController::class, 'open']);
        Route::post('/shifts/close', [ShiftController::class, 'close']);
        Route::post('/shifts/cash-movement', [ShiftController::class, 'cashMovement']);

        // Offline-First Sync Engine (Pull & Push)
        Route::get('/sync/pull', [SyncController::class, 'pull']);
        Route::post('/sync/push', [SyncController::class, 'push'])->middleware('throttle:60,1');

        // Authorization (PIN Void & PIN Diskon)
        Route::post('/authorize/pin', [AuthorizeController::class, 'verifyPin']);
    });
});
