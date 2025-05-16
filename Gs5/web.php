<?php

namespace Providers\Gs5;

use Illuminate\Support\Facades\Route;

Route::prefix('gs5')->group(function () {
    Route::prefix('in')->group(function () {
        Route::post('play', [Gs5Controller::class, 'play']);
        Route::post('visual', [Gs5Controller::class, 'visual']);
    });
    Route::prefix('prov')->group(function () {
        Route::get('api/getbalance/', [Gs5Controller::class, 'balance']);
        Route::get('api/authenticate/', [Gs5Controller::class, 'authenticate']);
        Route::get('api/bet/', [Gs5Controller::class, 'bet']);
        Route::get('api/refund/', [Gs5Controller::class, 'refund']);
        Route::get('api/result/', [Gs5Controller::class, 'result']);
    });
});
