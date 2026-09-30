<?php

declare(strict_types=1);

/*
 * Security headers (App\Http\Middleware\SecurityHeaders). An empty value in .env
 * counts as "not set", so it falls back to the default (on).
 */
$flag = fn (string $key, bool $default): bool => in_array(env($key), [null, ''], true) ? $default : (bool) env($key);

return [

    // Send the Content-Security-Policy header. Leave on; switch off only briefly to
    // diagnose a problem.
    'csp' => $flag('SECURITY_CSP', true),

    // Send Strict-Transport-Security (HTTPS in production only). Laravel sends it, so do
    // NOT also enable HSTS in HestiaCP (the header would be sent twice). Set false only
    // if the web server sends it instead.
    'hsts' => $flag('SECURITY_HSTS', true),

];
