<?php

namespace Providers\Bes;

use Illuminate\Support\Facades\Route;

Route::prefix('bes')->group(function () {
    Route::prefix('in')->group(function () {
        Route::post('play', [BesController::class, 'play']);
        Route::post('visual', [BesController::class, 'visual']);
        Route::post('update-game-position', [BesController::class, 'updateGamePosition']);
    });

    Route::post('prov', [BesController::class, 'entryPoint']);
});