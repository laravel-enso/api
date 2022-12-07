<?php

namespace LaravelEnso\Api\Http\Middleware;

use Closure;
use LaravelEnso\Api\Enums\Call;
use LaravelEnso\Api\Models\Log;
use LaravelEnso\Helpers\Services\Decimals;

class ApiLogger
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        Log::create([
            'user_id' => $request->user()?->id,
            'url' => $request->url(),
            'route' => $request->route()->getName(),
            'method' => $request->method(),
            'status' => $response->status(),
            'type' => Call::Inbound,
            'duration' => Decimals::sub(microtime(true), LARAVEL_START),
        ]);
    }
}
