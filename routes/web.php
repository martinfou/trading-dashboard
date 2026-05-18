<?php
use App\Http\Controllers\TradingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return auth()->check() ? redirect('/trading') : redirect('/login'); });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trading', [TradingController::class, 'index'])->name('trading');
    Route::get('/api/trading/refresh', [TradingController::class, 'refresh'])->name('trading.refresh');
});

require __DIR__.'/auth.php';
