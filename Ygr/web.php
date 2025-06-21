<?php

namespace Providers\Ygr;

use Illuminate\Support\Facades\Route;

Route::prefix('ygr')->group(function () {
    // INTEGRATOR
    Route::prefix('in')->group(function () {
        Route::post('play', [YgrController::class, 'play']);
        Route::post('visual', [YgrController::class, 'visual']);
    });
    // PROVIDER
    Route::prefix('prov')->group(function () {
        Route::post('token/authorizationConnectToken', [YgrController::class, 'authorizationConnectToken']);
        Route::get('token/getConnectTokenAmount', [YgrController::class, 'balance']);
        Route::post('token/delConnectToken', [YgrController::class, 'deleteToken']);
        Route::post('transaction/addGameResult', [YgrController::class, 'betAndSettle']);
    });
});
