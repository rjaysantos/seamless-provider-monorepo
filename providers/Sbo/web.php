<?php

namespace Providers\Sbo;

use Providers\Sbo\SboController;
use Illuminate\Support\Facades\Route;

Route::prefix('sbo')->group(function () {
    Route::prefix('in')->group(function () {
        Route::post('play', [SboController::class, 'play']);
        Route::post('visual', [SboController::class, 'visual']);
    });

    Route::prefix('prov')->group(function () {
        Route::post('GetBalance', [SboController::class, 'balance']);
        Route::post('Deduct', [SboController::class, 'deduct']);
        Route::post('Settle', [SboController::class, 'settle']);
        Route::post('Cancel', [SboController::class, 'cancel']);
        Route::post('Rollback', [SboController::class, 'rollback']);
        Route::post('GetBetStatus', [SboController::class, 'status']);
        Route::post('Bonus', [SboController::class, 'bonus']);
    });

    Route::prefix('sportsbooks')->group(function () {
        Route::post('outstanding', [SboController::class, 'outstanding']);
    });
});