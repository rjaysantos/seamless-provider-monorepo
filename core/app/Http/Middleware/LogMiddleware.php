<?php

namespace App\Http\Middleware;

use Closure;
use App\Libraries\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isEmpty;
use Symfony\Component\HttpFoundation\Response;

class LogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $logger = app()->make(Logger::class);
        $logger->startLog();

        return $next($request);
    }

    public function terminate($request, $response)
    {
        $logger = app()->make(Logger::class);

        if ($response->getStatusCode() != 404 && $request->getRequestUri() != '/')
            $logger->writeLog($request, $response->getContent());
    }
}
