<?php

namespace LaravelEnso\Api\Tests\Fixtures;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use LaravelEnso\Api\Action;
use LaravelEnso\Api\Contracts\AsForm;
use LaravelEnso\Api\Contracts\AttachesFiles;
use LaravelEnso\Api\Contracts\CustomHeaders;
use LaravelEnso\Api\Contracts\Endpoint;
use LaravelEnso\Api\Contracts\QueryParameters;
use LaravelEnso\Api\Contracts\Retry;
use LaravelEnso\Api\Contracts\Timeout;
use LaravelEnso\Api\Contracts\Token;
use LaravelEnso\Api\Contracts\UsesAuth;
use LaravelEnso\Api\Contracts\UsesBasicAuth;
use LaravelEnso\Api\Enums\Authorization;
use LaravelEnso\Api\Enums\Methods;
use LaravelEnso\Api\Resource;

class ApiFixtureTokenProvider implements Token
{
    public int $authCalls = 0;

    public function __construct(
        private string $token = 'initial-token',
        private string $type = Authorization::Bearer,
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function auth(): self
    {
        $this->authCalls++;
        $this->token = "refreshed-token-{$this->authCalls}";

        return $this;
    }

    public function current(): string
    {
        return $this->token;
    }
}

abstract class ApiFixtureEndpoint implements Endpoint
{
    public function __construct(
        protected string $url = 'https://api.test/resource',
        protected string|array $body = [],
        protected string $method = Methods::get,
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function body(): string|array
    {
        return $this->body;
    }
}

class ApiFixtureQueryEndpoint extends ApiFixtureEndpoint implements QueryParameters
{
    public function __construct(
        string $url = 'https://api.test/search',
        string|array $body = [],
        private array $parameters = [],
        string $method = Methods::get,
    ) {
        parent::__construct($url, $body, $method);
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}

class ApiFixtureRetryEndpoint extends ApiFixtureEndpoint implements Retry
{
    public function __construct(
        string $url = 'https://api.test/retry',
        string|array $body = [],
        private int $retryTries = 2,
        private int $retryDelay = 1,
        string $method = Methods::get,
    ) {
        parent::__construct($url, $body, $method);
    }

    public function delay(): int
    {
        return $this->retryDelay;
    }

    public function tries(): int
    {
        return $this->retryTries;
    }
}

class ApiFixtureAuthRetryEndpoint extends ApiFixtureEndpoint implements UsesAuth, Retry
{
    public function __construct(
        private ApiFixtureTokenProvider $tokenProvider,
        string $url = 'https://api.test/protected',
        string|array $body = [],
        private int $retryTries = 2,
        private int $retryDelay = 0,
        string $method = Methods::get,
    ) {
        parent::__construct($url, $body, $method);
    }

    public function tokenProvider(): Token
    {
        return $this->tokenProvider;
    }

    public function delay(): int
    {
        return $this->retryDelay;
    }

    public function tries(): int
    {
        return $this->retryTries;
    }
}

class ApiFixtureConfiguredEndpoint extends ApiFixtureEndpoint implements UsesBasicAuth, CustomHeaders, Timeout, AsForm, AttachesFiles
{
    public function __construct(
        string $url = 'https://api.test/forms',
        string|array $body = ['name' => 'enso'],
        string $method = Methods::post,
        private array $headers = ['X-Test-Header' => 'api'],
        private string $username = 'john',
        private string $password = 'secret',
        private int $timeout = 15,
    ) {
        parent::__construct($url, $body, $method);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }

    public function attach(PendingRequest $http): PendingRequest
    {
        return $http->attach('document', 'file-content', 'document.txt');
    }
}

class ApiFixtureAction extends Action
{
    public function __construct(
        private Endpoint $configuredEndpoint,
        private bool $enabled = true,
    ) {
    }

    protected function apiEnabled(): bool
    {
        return $this->enabled;
    }

    protected function endpoint(): Endpoint
    {
        return $this->configuredEndpoint;
    }
}

class ApiFixtureNestedResource extends Resource
{
    public function __construct(private array $payload)
    {
    }

    public function toArray(): array
    {
        return $this->payload;
    }
}

class ApiFixtureResource extends Resource
{
    public function __construct(private array $payload)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->payload['id'] ?? null,
            'name' => $this->payload['name'] ?? null,
            'nested' => new ApiFixtureNestedResource($this->payload['nested'] ?? []),
        ];
    }

    protected function mandatoryAttributes(): array
    {
        return ['id', 'name'];
    }

    public static function collectionFrom(array $items): array
    {
        return parent::collection(Collection::wrap($items));
    }
}
