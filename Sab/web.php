<?php

namespace Providers\Sab;

use Illuminate\Support\Facades\Route;
use Providers\Sab\Middleware\SabMiddleware;
use Providers\Sab\Middleware\SabBearerTokenCheckingMiddleware;

// SABA [SAB][Sportsbook]
Route::prefix('sab')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->middleware(SabBearerTokenCheckingMiddleware::class)->group(function () {
        Route::post('play', [SabController::class, 'play']);
        Route::post('visual', [SabController::class, 'visual']);
        Route::get('visual/{encryptedTrxID}', [SabController::class, 'visualHtml']);
    });
    // PROVIDER
    Route::prefix('prov')->middleware(SabMiddleware::class)->group(function () {
        Route::post('getbalance', [SabController::class, 'balance']);
        Route::post('placebet', [SabController::class, 'placeBet']);
        Route::post('placebetparlay', [SabController::class, 'placeBetParlay']);
        Route::post('cancelbet', [SabController::class, 'cancelBet']);
        Route::post('confirmbet', [SabController::class, 'confirmBet']);
        Route::post('confirmbetparlay', [SabController::class, 'confirmBet']);
        Route::post('settle', [SabController::class, 'settle']);
        Route::post('resettle', [SabController::class, 'resettle']);
        Route::post('unsettle', [SabController::class, 'unsettle']);
        Route::post('adjustbalance', [SabController::class, 'adjustBalance']);
    });

    Route::prefix('sportsbooks')->group(function () {
        Route::post('outstanding', [SabController::class, 'outstanding']);
    });
});
