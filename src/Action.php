<?php

namespace LaravelEnso\Api;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use LaravelEnso\Api\Contracts\Endpoint;
use LaravelEnso\Api\Contracts\QueryParameters;
use LaravelEnso\Api\Contracts\SoapEndpoint;
use LaravelEnso\Api\Enums\Direction;
use LaravelEnso\Api\Enums\Method;
use LaravelEnso\Api\Exceptions\Api as Exception;
use LaravelEnso\Api\Exceptions\Handler;
use LaravelEnso\Api\Models\Log;
use LaravelEnso\Helpers\Services\Decimals;
use Throwable;

abstract class Action
{
    private Api|SoapApi $api;
    private bool $handledFailure = false;

    public function handle(): Response|SoapResponse
    {
        if (!$this->apiEnabled()) {
            throw Exception::disabled($this);
        }

        try {
            $endpoint = $this->endpoint();
            $this->api = $endpoint instanceof SoapEndpoint
                ? App::make(SoapApi::class, ['endpoint' => $endpoint])
                : App::make(Api::class, ['endpoint' => $endpoint]);

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

    abstract protected function endpoint(): Endpoint|SoapEndpoint;

    private function log(Endpoint|SoapEndpoint $endpoint, Response|SoapResponse $response, string $duration): void
    {
        $queryParameters = $endpoint instanceof QueryParameters
            ? $endpoint->parameters()
            : [];

        $payload = [
            'queryParameters' => $queryParameters,
            'body' => $this->payload($endpoint),
        ];

        if ($endpoint instanceof SoapEndpoint) {
            $payload['operation'] = $endpoint->operation();
        }

        Log::create([
            'user_id' => Auth::user()?->id,
            'url' => $this->url($endpoint),
            'route' => Route::currentRouteName(),
            'method' => $this->method($endpoint),
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
            static::class, $this->url($endpoint), $this->payload($endpoint),
        ];

        $extra = match (true) {
            $response instanceof Response => [$response->status(), $response->body()],
            $response instanceof SoapResponse => [$response->code(), $response->message()],
            default => [$response->getCode(), $response->getMessage()],
        };

        return [...$base, ...$extra];
    }

    private function url(Endpoint|SoapEndpoint $endpoint): string
    {
        if ($endpoint instanceof Endpoint) {
            return $endpoint->url();
        }

        return $endpoint->wsdl()
            ?? $endpoint->options()['location']
            ?? $endpoint->operation();
    }

    private function method(Endpoint|SoapEndpoint $endpoint): Method
    {
        return $endpoint instanceof Endpoint
            ? $endpoint->method()
            : Method::POST;
    }

    private function payload(Endpoint|SoapEndpoint $endpoint): string|array
    {
        return $endpoint instanceof Endpoint
            ? $endpoint->body()
            : $endpoint->arguments();
    }
}
