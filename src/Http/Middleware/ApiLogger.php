<?php

namespace LaravelEnso\Api\Http\Middleware;

use Closure;
use LaravelEnso\Api\Enums\Direction;
use LaravelEnso\Api\Enums\Method;
use LaravelEnso\Api\Exceptions\Handler;
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
            'method' => Method::fromRequest($request),
            'status' => $response->status(),
            'direction' => Direction::Inbound,
            'duration' => Decimals::sub(microtime(true), LARAVEL_START),
        ]);

        if (!$response->isSuccessful()) {
            $this->report($request, $response);
        }
    }

    private function report($request, $response)
    {
        $args = [
            'Incoming Call', $request->url(), $request->all(),
            $response->status(), 'Api Call Failed',
        ];

        (new Handler(...$args))->report();
    }
}
