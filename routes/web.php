<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Unified Syaqil Laundry SPA
|--------------------------------------------------------------------------
|
| Route ini melayani antarmuka SPA terpadu (Kasir dan Owner) langsung
| dari port 8000. Setiap route web (non-API) akan menampilkan index.html
| hasil build Vite sehingga refresh browser tidak pernah menghasilkan 404.
|
*/

// Tautan Nota Digital Publik (Click-to-Chat WhatsApp Customer)
Route::get('/nota/{token}', [OrderController::class, 'publicReceipt'])->name('public.receipt');

Route::get('/login', function () {
    return redirect('/#login');
})->name('login');

Route::get('/{any?}', function () {
    $spaPath = public_path('index.html');
    if (file_exists($spaPath)) {
        return response()->file($spaPath, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    return response()->view('welcome');
})->where('any', '^(?!api).*$');
