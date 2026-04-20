<?php

require_once __DIR__.'/../Fixtures/ApiTestDoubles.php';
require_once __DIR__.'/../Support/sleep.php';

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use LaravelEnso\Api\Api;
use LaravelEnso\Api\Enums\Methods;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureAuthRetryEndpoint;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureConfiguredEndpoint;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureQueryEndpoint;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureRetryEndpoint;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureTokenProvider;
use LaravelEnso\Api\Tests\Support\ApiSleepRecorder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ApiSleepRecorder::reset();
        Config::set('enso.api.debug', false);
    }

    #[Test]
    public function refreshes_bearer_token_once_on_first_auth_failure(): void
    {
        $tokenProvider = new ApiFixtureTokenProvider();
        $endpoint = new ApiFixtureAuthRetryEndpoint($tokenProvider, body: ['query' => 'offers'], method: Methods::post);
        $attempts = 0;

        Http::fake(function ($request) use (&$attempts) {
            $attempts++;

            if ($attempts === 1) {
                $this->assertSame('Bearer initial-token', $request->header('Authorization')[0]);

                return Http::response(['message' => 'Unauthorized'], 401);
            }

            $this->assertSame('Bearer refreshed-token-1', $request->header('Authorization')[0]);

            return Http::response(['ok' => true], 200);
        });

        $response = (new Api($endpoint))->call();

        $this->assertTrue($response->successful());
        $this->assertSame(1, $tokenProvider->authCalls);
        $this->assertSame(2, $attempts);
    }

    #[Test]
    public function does_not_refresh_bearer_token_more_than_once(): void
    {
        $tokenProvider = new ApiFixtureTokenProvider();
        $endpoint = new ApiFixtureAuthRetryEndpoint($tokenProvider);
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            return Http::response(['message' => 'Still unauthorized'], 401);
        });

        $response = (new Api($endpoint))->call();

        $this->assertTrue($response->failed());
        $this->assertSame(1, $tokenProvider->authCalls);
        $this->assertSame(2, $attempts);
    }

    #[Test]
    public function retries_failed_calls_according_to_retry_contract(): void
    {
        $endpoint = new ApiFixtureRetryEndpoint(retryDelay: 2);
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            return $attempts === 1
                ? Http::response(['message' => 'Retry'], 500)
                : Http::response(['ok' => true], 200);
        });

        $api = new Api($endpoint);
        $response = $api->call();

        $this->assertTrue($response->successful());
        $this->assertSame(2, $api->tries());
        $this->assertSame([2], ApiSleepRecorder::$calls);
    }

    #[Test]
    public function appends_query_parameters_to_the_url(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $endpoint = new ApiFixtureQueryEndpoint(parameters: ['type' => 'offer', 'page' => 2]);

        (new Api($endpoint))->call();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.test/search?type=offer&page=2');
    }

    #[Test]
    public function applies_form_timeout_basic_auth_headers_and_attachments(): void
    {
        $pendingRequest = \Mockery::mock(PendingRequest::class);

        Http::shouldReceive('withHeaders')
            ->once()
            ->with([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Test-Header' => 'api',
            ])->andReturn($pendingRequest);

        $pendingRequest->shouldReceive('attach')
            ->once()
            ->with('document', 'file-content', 'document.txt')
            ->andReturnSelf();

        $pendingRequest->shouldReceive('timeout')
            ->once()
            ->with(15)
            ->andReturnSelf();

        $pendingRequest->shouldReceive('asForm')
            ->once()
            ->andReturnSelf();

        $pendingRequest->shouldReceive('withBasicAuth')
            ->once()
            ->with('john', 'secret')
            ->andReturnSelf();

        $pendingRequest->shouldReceive('withOptions')
            ->once()
            ->with(['debug' => false])
            ->andReturnSelf();

        $response = new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode(['ok' => true]))
        );

        $pendingRequest->shouldReceive('post')
            ->once()
            ->with('https://api.test/forms', ['name' => 'enso'])
            ->andReturn($response);

        $response = (new Api(new ApiFixtureConfiguredEndpoint()))->call();

        $this->assertTrue($response->successful());
    }

    #[Test]
    public function returns_null_body_for_non_post_empty_payloads(): void
    {
        $api = new Api(new ApiFixtureQueryEndpoint(body: [], method: Methods::get));

        $body = new \ReflectionMethod(Api::class, 'body');
        $body->setAccessible(true);

        $this->assertNull($body->invoke($api));
    }
}
