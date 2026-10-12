<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OwnerController;
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

        // Modul 3: Operasional Kasir & Perhitungan Transaksi Backend
        // Dapat diakses oleh kasir dan owner
        Route::middleware(['role:cashier,owner'])->group(function () {
            Route::get('/bootstrap', [OrderController::class, 'bootstrap']);
            Route::post('/customers', [CustomerController::class, 'store']);
            Route::post('/customers/{id}', [CustomerController::class, 'update']);
            Route::post('/orders', [OrderController::class, 'store']);
            Route::get('/orders/{id}', [OrderController::class, 'show']);
            Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);

            Route::get('/cashier/ping', function (Request $request) {
                return response()->json([
                    'ok' => true,
                    'role' => $request->user()->role,
                    'message' => 'Akses Kasir Terverifikasi'
                ]);
            });
        });

        // Modul 4: Manajemen Owner, Pengeluaran & Laporan Keuangan
        // Khusus peran Owner
        Route::middleware(['role:owner'])->prefix('owner')->group(function () {
            Route::get('/bootstrap', [OwnerController::class, 'bootstrap']);
            Route::get('/reports', [OwnerController::class, 'reports']);

            // Pengeluaran
            Route::post('/expenses', [OwnerController::class, 'storeExpense']);
            Route::post('/expenses/{id}', [OwnerController::class, 'updateExpense']);
            Route::delete('/expenses/{id}', [OwnerController::class, 'deleteExpense']);

            // Layanan & Tarif
            Route::post('/services', [OwnerController::class, 'storeService']);
            Route::post('/services/{id}', [OwnerController::class, 'updateService']);
            Route::post('/services/{id}/toggle', [OwnerController::class, 'toggleService']);

            // Karyawan
            Route::post('/employees', [OwnerController::class, 'storeEmployee']);
            Route::post('/employees/{id}', [OwnerController::class, 'updateEmployee']);
            Route::post('/employees/{id}/reset-password', [OwnerController::class, 'resetEmployeePassword']);

            // Koreksi & Pembatalan
            Route::post('/corrections', [OwnerController::class, 'storeCorrection']);

            // Pengaturan
            Route::post('/settings', [OwnerController::class, 'updateSettings']);

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
