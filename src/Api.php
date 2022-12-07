<?php

namespace LaravelEnso\Api;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelEnso\Api\Contracts\AttachesFiles;
use LaravelEnso\Api\Contracts\CustomHeaders;
use LaravelEnso\Api\Contracts\Endpoint;
use LaravelEnso\Api\Contracts\QueryParameters;
use LaravelEnso\Api\Contracts\Retry;
use LaravelEnso\Api\Contracts\Timeout;
use LaravelEnso\Api\Contracts\UsesAuth;
use LaravelEnso\Api\Enums\Method;
use LaravelEnso\Api\Enums\ResponseCode;

class Api
{
    protected int $tries;

    public function __construct(protected Endpoint $endpoint)
    {
        $this->tries = 0;
    }

    public function call(): Response
    {
        $this->tries++;

        $response = $this->response();

        if ($response->failed()) {
            if ($this->possibleTokenExpiration($response)) {
                $this->endpoint->tokenProvider()->auth();

                return $this->call();
            }

            if ($this->shouldRetry()) {
                sleep($this->endpoint->delay());

                return $this->call();
            }
        }

        return $response;
    }

    public function tries(): int
    {
        return $this->tries;
    }

    protected function response(): Response
    {
        $http = Http::withHeaders($this->headers());

        if ($this->endpoint instanceof AttachesFiles) {
            $this->endpoint->attach($http);
        }

        if ($this->endpoint instanceof Timeout) {
            $http->timeout($this->endpoint->timeout());
        }

        return $http->withOptions(['debug' => Config::get('enso.api.debug')])
            ->{$this->endpoint->method()->value}($this->url(), $this->body());
    }

    protected function headers(): array
    {
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];

        if ($this->endpoint instanceof CustomHeaders) {
            $headers += $this->endpoint->headers();
        }

        if ($this->endpoint instanceof UsesAuth) {
            $token = $this->endpoint->tokenProvider();
            $headers['Authorization'] = "{$token->type()->value} {$token->current()}";
        }

        return $headers;
    }

    protected function shouldRetry(): bool
    {
        return $this->endpoint instanceof Retry
            && $this->tries < $this->endpoint->tries();
    }

    protected function possibleTokenExpiration(Response $response): bool
    {
        return $this->endpoint instanceof UsesAuth
            && ResponseCode::from($response->status())->needsAuth()
            && $this->tries === 1;
    }

    protected function body(): string|array|null
    {
        if ($this->endpoint->method() === Method::POST) {
            return $this->endpoint->body();
        }

        return $this->endpoint->body() ?: null;
    }

    protected function url(): string
    {
        if ($this->endpoint instanceof QueryParameters) {
            $params = Arr::query($this->endpoint->parameters());

            return Str::of($this->endpoint->url())
                ->when($params, fn ($path) => $path->append("?{$params}"));
        }

        return $this->endpoint->url();
    }
}
