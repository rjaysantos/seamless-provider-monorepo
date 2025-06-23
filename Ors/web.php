<?php

namespace Providers\Ors;

use Illuminate\Support\Facades\Route;

Route::prefix('ors')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->group(function () {
        Route::post('play', [OrsController::class, 'play']);
        Route::post('visual', [OrsController::class, 'visual']);
    });
    // PROVIDER
    Route::prefix('prov')->group(function () {
        Route::post('api/v2/operator/security/authenticate', [OrsController::class, 'authenticate']);
        Route::get('api/v2/operator/player/balance', [OrsController::class, 'balance']);
        Route::post('api/v2/operator/transaction/credit', [OrsController::class, 'credit']);
        Route::post('api/v2/operator/transaction/bulk/debit', [OrsController::class, 'debit']);
        Route::post('api/v2/operator/transaction/reward', [OrsController::class, 'reward']);
    });
});
