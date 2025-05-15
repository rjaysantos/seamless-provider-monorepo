<?php

namespace Providers\Sab\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SabMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('Content-Encoding') === 'gzip') {
            $compressedContent = $request->getContent();
            $decompressedContent = gzdecode($compressedContent);

            $request->replace(json_decode($decompressedContent, true));
        }

        return $next($request);
    }
}
