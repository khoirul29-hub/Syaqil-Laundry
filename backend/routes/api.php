<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Syaqil Laundry (Kasir & Owner)
|--------------------------------------------------------------------------
*/

// Public Authentication & Session Routes
Route::middleware(['web'])->group(function () {
    Route::get('/session', [AuthController::class, 'session']);
    Route::post('/login', [AuthController::class, 'login']);

    // Authenticated Common Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/user', function (Request $request) {
            return response()->json(['user' => $request->user()]);
        });

        // Cashier Protected Endpoints (Accessible by Cashier and Owner)
        Route::middleware(['role:cashier,owner'])->prefix('cashier')->group(function () {
            Route::get('/ping', function (Request $request) {
                return response()->json([
                    'ok' => true,
                    'role' => $request->user()->role,
                    'message' => 'Akses Kasir Terverifikasi'
                ]);
            });
        });

        // Owner Only Protected Endpoints (Strictly Owner role)
        Route::middleware(['role:owner'])->prefix('owner')->group(function () {
            Route::get('/ping', function (Request $request) {
                return response()->json([
                    'ok' => true,
                    'role' => $request->user()->role,
                    'message' => 'Akses Owner Terverifikasi'
                ]);
            });
        });
    });
});
