<?php

namespace Providers\Jdb;

use Illuminate\Support\Facades\Route;

Route::prefix('jdb')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->group(function () {
        Route::post('play', [JdbController::class, 'play']);
        Route::post('visual', [JdbController::class, 'visual']);
    });
    // PROVIDER
    Route::prefix('prov')->group(function () {
        Route::post('{currency}', [JdbController::class, 'entryPoint']);
    });
});