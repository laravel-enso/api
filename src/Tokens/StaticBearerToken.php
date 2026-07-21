<?php

namespace LaravelEnso\Api\Tokens;

use Illuminate\Support\Facades\Config;
use LaravelEnso\Api\Contracts\Token;
use LaravelEnso\Api\Enums\Authorization;
use RuntimeException;

class StaticBearerToken implements Token
{
    public function __construct(private string $configKey)
    {
    }

    public function type(): string
    {
        return Authorization::Bearer;
    }

    public function auth(): self
    {
        return $this;
    }

    public function current(): string
    {
        $token = Config::get($this->configKey);

        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException(
                "API token [{$this->configKey}] is not configured.",
            );
        }

        return $token;
    }
}
