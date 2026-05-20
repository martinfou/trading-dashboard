
// Strategy Lifecycle API (deploy.sh → Laravel)
Route::post('/deployments', [App\Http\Controllers\Api\DeploymentController::class, 'store']);
Route::post('/deployments/{id}/trades', [App\Http\Controllers\Api\DeploymentController::class, 'importTrades']);
Route::post('/deployments/{id}/metrics', [App\Http\Controllers\Api\DeploymentController::class, 'updateMetrics']);
Route::get('/strategies', [App\Http\Controllers\Api\DeploymentController::class, 'index']);
Route::get('/strategies/{strategy}', [App\Http\Controllers\Api\DeploymentController::class, 'timeline']);
Route::get('/deployments/{id}', [App\Http\Controllers\Api\DeploymentController::class, 'show']);
