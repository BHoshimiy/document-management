<?php

declare(strict_types=1);

// Proxies whose X-Forwarded-* headers are trusted. Empty = trust none.
// Set to "*" for a local ngrok/tunnel so https is detected; in production list the
// load balancer IPs instead — trusting "*" lets clients spoof $request->ip(),
// which the auth rate limiter keys on.
return [
    'proxies' => env('TRUSTED_PROXIES'),
];
