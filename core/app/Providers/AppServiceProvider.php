<?php

namespace App\Providers;

use App\Libraries\Logger;
use App\Contracts\V2\IWallet;
use Illuminate\Support\ServiceProvider;
use App\Libraries\Wallet\V2\LoggingDecorator;
use App\Libraries\Wallet\V2\Wallet;

class AppServiceProvider extends ServiceProvider
{
    public $bindings = [];

    public $singletons = [
        Logger::class => Logger::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        app()->bind(IWallet::class, function () {
            $wallet = new Wallet;
            return new LoggingDecorator($wallet);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
