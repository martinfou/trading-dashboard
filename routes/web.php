<?php
use App\Http\Controllers\TradingController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Api\TradingApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return auth()->check() ? redirect('/trading') : redirect('/login'); });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trading', [TradingController::class, 'index'])->name('trading');
    Route::get('/dashboard', function () { return redirect('/trading'); })->name('dashboard');
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');
    Route::get('/api/trading/prices', [TradingApiController::class, 'prices'])->name('api.trading.prices');
    Route::get('/api/trading/refresh', [TradingApiController::class, 'refresh'])->name('api.trading.refresh');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trades', [App\Http\Controllers\TradeHistoryController::class, 'index'])->name('trades');
    Route::patch('/trades/{trade}', [App\Http\Controllers\TradeHistoryController::class, 'update'])->name('trades.update');
    Route::post('/trades/{trade}/comments', [App\Http\Controllers\TradeHistoryController::class, 'addComment'])->name('trades.comments');
    Route::get('/signals', [App\Http\Controllers\WeeklySignalsController::class, 'index'])->name('signals');
    Route::post('/signals', [App\Http\Controllers\WeeklySignalsController::class, 'store'])->name('signals.store');
});
