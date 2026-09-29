<?php

declare(strict_types=1);

return [

    // Send the Content-Security-Policy header (App\Http\Middleware\SecurityHeaders).
    // Leave on; switch off only briefly to diagnose a problem.
    'csp' => (bool) env('SECURITY_CSP', true),

];
