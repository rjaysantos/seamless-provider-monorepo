<?php

namespace Providers\Red;

use Illuminate\Support\Facades\Route;

Route::prefix('red')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->group(function () {
        Route::post('play', [RedController::class, 'play']);
        Route::post('visual', [RedController::class, 'visual']);
    });
    // PROVIDER
    Route::prefix('prov')->group(function () {
        Route::post('balance', [RedController::class, 'balance']);
        Route::post('debit', [RedController::class, 'wager']);
        Route::post('credit', [RedController::class, 'payout']);
        Route::post('bonus', [RedController::class, 'bonus']);
    });
});
