<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ExternalLinks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Applies the external-link rule (App\Support\ExternalLinks) to every public HTML
 * page, so links in templates and in content written in the CRM are all covered.
 * The CRM panel itself (/crm) is left to Filament.
 */
class ProcessExternalLinks
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->is('crm', 'crm/*', 'livewire*')
            || $response instanceof StreamedResponse
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
        ) {
            return $response;
        }

        $content = $response->getContent();

        if (is_string($content) && $content !== '') {
            $response->setContent(ExternalLinks::process($content));
        }

        return $response;
    }
}
