<?php

declare(strict_types=1);

namespace LiteCrm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps every CRM response out of search engines.
 */
class NoIndex
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
