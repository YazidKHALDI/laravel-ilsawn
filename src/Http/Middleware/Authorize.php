<?php

namespace ilsawn\LaravelIlsawn\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ilsawn\LaravelIlsawn\LaravelIlsawn;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        return LaravelIlsawn::check($request) ? $next($request) : abort(403);
    }
}
