<?php

namespace Providers\Hcg;

use Illuminate\Support\Facades\Route;

//HCG [HCG][SLOT] Provider
Route::prefix('hcg')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->group(function () {
        Route::post('play', [HcgController::class, 'play']);
        Route::post('visual', [HcgController::class, 'visual']);
    });
    // PROVIDER
    Route::prefix('prov')->group(function () {
        Route::post('{currency}', [HcgController::class, 'entryPoint']);
    });
});