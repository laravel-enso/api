<?php

namespace LaravelEnso\Api;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use LaravelEnso\Api\Contracts\ServiceAddress;
use LaravelEnso\Api\Enums\Calls;
use LaravelEnso\Api\Exceptions\Handler;
use LaravelEnso\Api\Exceptions\Service as Exception;
use LaravelEnso\Api\Models\Log;
use LaravelEnso\Helpers\Services\Decimals;
use stdClass;
use Throwable;

abstract class Operation
{
    protected Service $service;

    public function handle(): stdClass
    {
        if (! $this->serviceEnabled()) {
            throw Exception::disabled($this);
        }

        try {
            $this->service = App::make(Service::class, ['serviceAddress' => $this->serviceAddress()]);

            $timer = microtime(true);

            $response = $this->service->call();

            $duration = Decimals::sub(microtime(true), $timer);

            $this->log($duration);

            return $response;
        } catch (Throwable $exception) {
            (new Handler(...$this->args($exception)))->report();

            throw $exception;
        }
    }

    protected function serviceEnabled(): bool
    {
        return true;
    }

    abstract protected function serviceAddress(): ServiceAddress;

    protected function log(string $duration): void
    {
        Log::create([
            'user_id' => Auth::user()?->id,
            'url' => $this->serviceAddress()->url(),
            'route' => Route::currentRouteName(),
            'method' => $this->serviceAddress()->operation(),
            'status' => 200,
            'try' => $this->service->tries(),
            'type' => Calls::Outbound,
            'duration' => $duration,
            'payload' => $this->serviceAddress()->params(),
        ]);
    }

    protected function args(Throwable $exception): array
    {
        return [
            static::class,
            $this->serviceAddress()->url(),
            $this->serviceAddress()->params(),
            $exception->getCode(),
            $exception->getMessage(),
        ];
    }
}
