<?php

namespace Providers\Sab\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\Casino\InvalidBearerTokenException;

class SabBearerTokenCheckingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('sab/in/visual/*'))
            return $next($request);

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;

        return $next($request);
    }
}
