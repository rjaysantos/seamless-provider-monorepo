
<?php

use Providers\Pla\PlaController;
use Illuminate\Support\Facades\Route;

Route::prefix('pla')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->group(function () {
        Route::post('play', [PlaController::class, 'play']);
        Route::post('visual', [PlaController::class, 'visual']);
    });
    // PROVIDER
    Route::prefix('prov')->group(function () {
        Route::post('authenticate', [PlaController::class, 'authenticate']);
        Route::post('getbalance', [PlaController::class, 'getBalance']);
        Route::post('bet', [PlaController::class, 'bet']);
        Route::post('gameroundresult', [PlaController::class, 'gameRoundResult']);
        Route::post('logout', [PlaController::class, 'logout']);
        Route::post('healthcheck', [PlaController::class, 'healthCheck']);
    });
});