<?php

namespace LaravelEnso\Api\Tests\Fixtures;

require_once __DIR__.'/SoapStubs.php';

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use LaravelEnso\Api\Action;
use LaravelEnso\Api\Contracts\AsForm;
use LaravelEnso\Api\Contracts\AttachesFiles;
use LaravelEnso\Api\Contracts\CustomHeaders;
use LaravelEnso\Api\Contracts\Endpoint;
use LaravelEnso\Api\Contracts\QueryParameters;
use LaravelEnso\Api\Contracts\Retry;
use LaravelEnso\Api\Contracts\SoapEndpoint;
use LaravelEnso\Api\Contracts\Timeout;
use LaravelEnso\Api\Contracts\Token;
use LaravelEnso\Api\Contracts\UsesAuth;
use LaravelEnso\Api\Contracts\UsesBasicAuth;
use LaravelEnso\Api\Endpoints\Soap;
use LaravelEnso\Api\Enums\Authorization;
use LaravelEnso\Api\Enums\Method;
use LaravelEnso\Api\Resource;
use LaravelEnso\Api\SoapApi;
use SoapClient;
use SoapFault;

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
        protected Method $method = Method::GET,
    ) {
    }

    public function method(): Method
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
        Method $method = Method::GET,
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
        Method $method = Method::GET,
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
        Method $method = Method::GET,
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
        Method $method = Method::POST,
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

class ApiFixtureSoapEndpoint extends Soap
{
    public function __construct(
        private ?string $wsdl = 'https://soap.test/service.wsdl',
        private string $operation = 'Submit',
        private array $arguments = [['id' => 1]],
        private array $options = ['trace' => true],
    ) {
    }

    public function wsdl(): ?string
    {
        return $this->wsdl;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

    public function options(): array
    {
        return $this->options;
    }
}

class ApiFixtureRetrySoapEndpoint extends ApiFixtureSoapEndpoint implements Retry
{
    public function __construct(
        ?string $wsdl = 'https://soap.test/service.wsdl',
        string $operation = 'Submit',
        array $arguments = [['id' => 1]],
        array $options = ['trace' => true],
        private int $retryTries = 2,
        private int $retryDelay = 1,
    ) {
        parent::__construct($wsdl, $operation, $arguments, $options);
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

class ApiFixtureSoapAction extends Action
{
    public function __construct(private SoapEndpoint $configuredEndpoint)
    {
    }

    protected function endpoint(): SoapEndpoint
    {
        return $this->configuredEndpoint;
    }
}

class ApiFixtureSoapApi extends SoapApi
{
    public function __construct(SoapEndpoint $endpoint, private SoapClient $soapClient)
    {
        parent::__construct($endpoint);
    }

    protected function client(): SoapClient
    {
        return $this->soapClient;
    }
}

class ApiFixtureSoapClient extends SoapClient
{
    public array $calls = [];

    public function __construct(private array $responses)
    {
    }

    public function __soapCall(
        string $name,
        array $args,
        ?array $options = null,
        $inputHeaders = null,
        &$outputHeaders = null
    ): mixed {
        $this->calls[] = compact('name', 'args', 'options', 'inputHeaders');
        $response = array_shift($this->responses);

        if ($response instanceof SoapFault) {
            throw $response;
        }

        return $response;
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
