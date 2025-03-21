<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::middleware(['throttle:nasa-api'])->group(function () {
    Route::get('/nasa', [ApiController::class, 'nasa']);
    Route::get('/instruments', [ApiController::class, 'instruments']);
    Route::get('/activityid', [ApiController::class, 'activityid']);
    Route::get('/instruments-use', [ApiController::class, 'instrumentsUse']);
    Route::post('/instrument-usage', [ApiController::class, 'getInstrumentUsage']);
});