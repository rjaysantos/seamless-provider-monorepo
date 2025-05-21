
<?php

use Providers\Pca\PcaController;
use Illuminate\Support\Facades\Route;

Route::prefix('pca')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->group(function () {
        Route::post('play', [PcaController::class, 'play']);
        Route::post('visual', [PcaController::class, 'visual']);
    });
    // PROVIDER
    Route::prefix('prov')->group(function () {
        Route::post('authenticate', [PcaController::class, 'authenticate']);
        Route::post('getbalance', [PcaController::class, 'getBalance']);
        Route::post('bet', [PcaController::class, 'bet']);
        Route::post('gameroundresult', [PcaController::class, 'gameRoundResult']);
        Route::post('logout', [PcaController::class, 'logout']);
        Route::post('healthcheck', [PcaController::class, 'healthCheck']);
    });
});