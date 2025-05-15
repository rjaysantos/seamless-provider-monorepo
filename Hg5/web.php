<?php

namespace Providers\Hg5;

use Illuminate\Support\Facades\Route;

Route::prefix('hg5')->group(function () {
    Route::prefix('in')->group(function () {
        Route::post('play', [Hg5Controller::class, 'play']);
        Route::post('visual', [Hg5Controller::class, 'visual']);
        Route::get('visual/{encryptedPlayID}/{encryptedTrxID}', [Hg5Controller::class, 'visualHtml']);
        Route::get('visual/fishgame', [Hg5Controller::class, 'visualFishGame']);
    });
    Route::prefix('prov')->group(function () {
        Route::post('GrandPriest/fetchBalance', [Hg5Controller::class, 'balance']);
        Route::post('GrandPriest/authenticate', [Hg5Controller::class, 'authenticate']);
        Route::post('GrandPriest/withdraw_deposit', [Hg5Controller::class, 'withdrawAndDeposit']);
        Route::post('GrandPriest/withdraw', [Hg5Controller::class, 'withdraw']);
        Route::post('GrandPriest/deposit', [Hg5Controller::class, 'deposit']);
        Route::post('GrandPriest/multi_withdraw', [Hg5Controller::class, 'multipleWithdraw']);
        Route::post('GrandPriest/multi_deposit', [Hg5Controller::class, 'multipleDeposit']);
        Route::post('GrandPriest/rollout', [Hg5Controller::class, 'rollout']);
        Route::post('GrandPriest/rollin', [Hg5Controller::class, 'rollin']);
    });
});
