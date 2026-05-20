<?php
use App\Http\Controllers\TradingController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Api\TradingApiController;
use App\Http\Controllers\BacktestController;
use App\Http\Controllers\StrategyGenController;
use App\Http\Controllers\StrategyRegistryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return auth()->check() ? redirect('/trading') : redirect('/login'); });

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trading', [TradingController::class, 'index'])->name('trading');
    Route::get('/dashboard', function () { return redirect('/trading'); })->name('dashboard');
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');
    Route::get('/api/trading/prices', [TradingApiController::class, 'prices'])->name('api.trading.prices');
    Route::get('/api/trading/refresh', [TradingApiController::class, 'refresh'])->name('api.trading.refresh');

// Strategy Lifecycle API (deploy.sh → Laravel)
Route::post('/api/deployments', [App\Http\Controllers\Api\DeploymentController::class, 'store']);
Route::post('/api/deployments/{id}/trades', [App\Http\Controllers\Api\DeploymentController::class, 'importTrades']);
Route::post('/api/deployments/{id}/metrics', [App\Http\Controllers\Api\DeploymentController::class, 'updateMetrics']);
Route::get('/api/strategies', [App\Http\Controllers\Api\DeploymentController::class, 'index']);
Route::get('/api/strategies/{strategy}', [App\Http\Controllers\Api\DeploymentController::class, 'timeline']);
Route::get('/api/deployments/{id}', [App\Http\Controllers\Api\DeploymentController::class, 'show']);
});  // end auth group

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/strategies', [StrategyRegistryController::class, 'index'])->name('strategies.index');
    Route::get('/backtest', [BacktestController::class, 'index'])->name('backtest.index');
    Route::get('/backtest/{strategy}', [BacktestController::class, 'show'])->name('backtest.show');
    Route::post('/backtest/run', [BacktestController::class, 'run'])->name('backtest.run');
    Route::get('/strategy', [StrategyGenController::class, 'index'])->name('strategy.index');
    Route::post('/strategy/generate', [StrategyGenController::class, 'generate'])->name('strategy.generate');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trades', [App\Http\Controllers\TradeHistoryController::class, 'index'])->name('trades');
    Route::patch('/trades/{trade}', [App\Http\Controllers\TradeHistoryController::class, 'update'])->name('trades.update');
    Route::post('/trades/{trade}/comments', [App\Http\Controllers\TradeHistoryController::class, 'addComment'])->name('trades.comments');
    Route::get('/signals', [App\Http\Controllers\WeeklySignalsController::class, 'index'])->name('signals');
    Route::post('/signals', [App\Http\Controllers\WeeklySignalsController::class, 'store'])->name('signals.store');
});
Route::get('/strategies', function () {
    return view('strategies');
})->middleware(['auth', 'verified'])->name('strategies');
