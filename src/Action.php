<?php

namespace LaravelEnso\Api;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use LaravelEnso\Api\Contracts\Client;
use LaravelEnso\Api\Contracts\Endpoint;
use LaravelEnso\Api\Contracts\QueryParameters;
use LaravelEnso\Api\Contracts\SoapEndpoint;
use LaravelEnso\Api\Enums\Direction;
use LaravelEnso\Api\Exceptions\Api as Exception;
use LaravelEnso\Api\Exceptions\Handler;
use LaravelEnso\Api\Models\Log;
use LaravelEnso\Helpers\Services\Decimals;
use Throwable;

abstract class Action
{
    private Client $api;
    private bool $handledFailure = false;

    public function handle(): Response|SoapResponse
    {
        if (!$this->apiEnabled()) {
            throw Exception::disabled($this);
        }

        try {
            $endpoint = $this->endpoint();
            $this->api = $this->client($endpoint);

            $timer = microtime(true);

            $response = $this->api->call();

            $duration = Decimals::sub(microtime(true), $timer);

            $this->log($endpoint, $response, $duration);

            if ($response->failed()) {
                (new Handler(...$this->args($response)))->report();
                $this->handledFailure = true;
            }

            return $response->throw();
        } catch (Throwable $exception) {
            if (!$this->handledFailure) {
                (new Handler(...$this->args($exception)))->report();
            }

            throw $exception;
        }
    }

    protected function apiEnabled(): bool
    {
        return true;
    }

    abstract protected function endpoint(): Endpoint;

    private function client(Endpoint $endpoint): Client
    {
        return $endpoint instanceof SoapEndpoint
            ? App::make(SoapApi::class, ['endpoint' => $endpoint])
            : App::make(Api::class, ['endpoint' => $endpoint]);
    }

    private function log(Endpoint $endpoint, Response|SoapResponse $response, string $duration): void
    {
        $queryParameters = $endpoint instanceof QueryParameters
            ? $endpoint->parameters()
            : [];

        $payload = [
            'queryParameters' => $queryParameters,
            'body' => $endpoint->body(),
        ];

        if ($endpoint instanceof SoapEndpoint) {
            $payload['operation'] = $endpoint->operation();
        }

        Log::create([
            'user_id' => Auth::user()?->id,
            'url' => $endpoint->url(),
            'route' => Route::currentRouteName(),
            'method' => $endpoint->method(),
            'status' => $response->status(),
            'try' => $this->api->tries(),
            'direction' => Direction::Outbound,
            'duration' => $duration,
            'payload' => $payload,
        ]);
    }

    private function args(Throwable|Response|SoapResponse $response): array
    {
        $endpoint = $this->endpoint();
        $base = [
            static::class, $endpoint->url(), $endpoint->body(),
        ];

        $extra = match (true) {
            $response instanceof Response => [$response->status(), $response->body()],
            $response instanceof SoapResponse => [$response->code(), $response->message()],
            default => [$response->getCode(), $response->getMessage()],
        };

        return [...$base, ...$extra];
    }
}
