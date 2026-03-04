<?php

namespace ilsawn\LaravelIlsawn\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Gate::check('viewIlsawn', [$request->user()])) {
            abort(403);
        }

        return $next($request);
    }
}
