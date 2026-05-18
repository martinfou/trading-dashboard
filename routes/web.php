<?php
use App\Http\Controllers\TradingController;
use App\Http\Controllers\Api\TradingApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return auth()->check() ? redirect('/trading') : redirect('/login'); });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trading', [TradingController::class, 'index'])->name('trading');
    Route::get('/api/trading/prices', [TradingApiController::class, 'prices'])->name('api.trading.prices');
    Route::get('/api/trading/refresh', [TradingApiController::class, 'refresh'])->name('api.trading.refresh');
    Route::get('/api/trading/stats', [TradingApiController::class, 'stats'])->name('api.trading.stats');
});

require __DIR__.'/auth.php';
