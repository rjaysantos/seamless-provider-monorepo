<?php

namespace App\GameProviders\V2\PCA\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\GameProviders\V2\PCA\PcaApi;
use App\GameProviders\V2\PLA\PlaApi;
use App\GameProviders\V2\PCA\PcaRepository;
use App\GameProviders\V2\PLA\PlaRepository;
use App\GameProviders\V2\PCA\Contracts\IApi;
use App\GameProviders\V2\PCA\PcaCredentials;
use App\GameProviders\V2\PLA\PlaCredentials;
use App\GameProviders\V2\PCA\PcaWalletReport;
use App\GameProviders\V2\PLA\PlaWalletReport;
use App\GameProviders\V2\PCA\Contracts\IRepository;
use App\GameProviders\V2\PCA\Contracts\IWalletReport;
use App\GameProviders\V2\PCA\Contracts\ICredentialSetter;

class PlaytechMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->path(), 'pla/') === true) {
            app()->bind(ICredentialSetter::class, PlaCredentials::class);
            app()->bind(IRepository::class, PlaRepository::class);
            app()->bind(IApi::class, PlaApi::class);
            app()->bind(IWalletReport::class, PlaWalletReport::class);
        }else{
            app()->bind(ICredentialSetter::class, PcaCredentials::class);
            app()->bind(IRepository::class, PcaRepository::class);
            app()->bind(IApi::class, PcaApi::class);
            app()->bind(IWalletReport::class, PcaWalletReport::class);
        }

        return $next($request);
    }
}