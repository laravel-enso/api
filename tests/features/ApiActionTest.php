<?php

require_once __DIR__.'/../Fixtures/SoapStubs.php';
require_once __DIR__.'/../Fixtures/ApiTestDoubles.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use LaravelEnso\Api\Enums\Direction;
use LaravelEnso\Api\Enums\Method;
use LaravelEnso\Api\Exceptions\Api as ApiDisabled;
use LaravelEnso\Api\Models\Log;
use LaravelEnso\Api\Notifications\ApiCallError;
use LaravelEnso\Api\SoapApi;
use LaravelEnso\Api\SoapResponse;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureAction;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureQueryEndpoint;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapAction;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapApi;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapClient;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapEndpoint;
use LaravelEnso\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->user = User::first();
    }

    #[Test]
    public function returns_successful_response_and_logs_outbound_calls(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAs($this->user);

        $response = (new ApiFixtureAction(
            new ApiFixtureQueryEndpoint(parameters: ['page' => 2], body: ['search' => 'offers'])
        ))->handle();

        $log = Log::latest()->first();

        $this->assertTrue($response->successful());
        $this->assertNotNull($log);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertSame('https://api.test/search', $log->url);
        $this->assertNull($log->route);
        $this->assertSame(Method::GET, $log->method);
        $this->assertSame(Method::GET->value, $log->getRawOriginal('method'));
        $this->assertSame(200, $log->status);
        $this->assertSame(1, $log->try);
        $this->assertSame(Direction::Outbound, $log->direction);
        $this->assertSame(Direction::Outbound->value, $log->getRawOriginal('direction'));
        $this->assertSame(['page' => 2], $log->payload['queryParameters']);
        $this->assertSame(['search' => 'offers'], $log->payload['body']);
    }

    #[Test]
    public function reports_failed_responses_only_once_and_throws_request_exception(): void
    {
        Notification::fake();

        Http::fake([
            '*' => Http::response(['message' => 'Failed'], 422),
        ]);

        try {
            $this->actingAs($this->user);
            (new ApiFixtureAction(new ApiFixtureQueryEndpoint()))->handle();
            $this->fail('The action should throw a RequestException');
        } catch (RequestException) {
            $admins = User::active()->admins()->get();

            $this->assertDatabaseCount('api_logs', 1);
            Notification::assertSentTo($admins, ApiCallError::class);
            Notification::assertCount($admins->count());
        }
    }

    #[Test]
    public function reports_thrown_exceptions(): void
    {
        Notification::fake();

        Http::fake(fn () => throw new RuntimeException('Boom'));

        try {
            $this->actingAs($this->user);
            (new ApiFixtureAction(new ApiFixtureQueryEndpoint()))->handle();
            $this->fail('The action should rethrow the runtime exception');
        } catch (RuntimeException $exception) {
            $admins = User::active()->admins()->get();

            $this->assertSame('Boom', $exception->getMessage());
            $this->assertDatabaseCount('api_logs', 0);
            Notification::assertSentTo($admins, ApiCallError::class);
            Notification::assertCount($admins->count());
        }
    }

    #[Test]
    public function throws_when_the_api_is_disabled(): void
    {
        $this->expectException(ApiDisabled::class);
        $this->expectExceptionMessage('Api API is disabled');

        (new ApiFixtureAction(new ApiFixtureQueryEndpoint(), false))->handle();
    }

    #[Test]
    public function returns_successful_soap_response_and_logs_outbound_calls(): void
    {
        $endpoint = new ApiFixtureSoapEndpoint(
            operation: 'SubmitInvoice',
            arguments: [['number' => 'INV-1']],
        );
        $client = new ApiFixtureSoapClient([['accepted' => true]]);

        $this->app->bind(SoapApi::class, fn ($app, $params) => new ApiFixtureSoapApi(
            $params['endpoint'],
            $client,
        ));

        $this->actingAs($this->user);

        $response = (new ApiFixtureSoapAction($endpoint))->handle();
        $log = Log::latest()->first();

        $this->assertInstanceOf(SoapResponse::class, $response);
        $this->assertTrue($response->successful());
        $this->assertSame(['accepted' => true], $response->body());
        $this->assertSame('https://soap.test/service.wsdl', $log->url);
        $this->assertSame(Method::POST, $log->method);
        $this->assertSame([['number' => 'INV-1']], $log->payload['body']);
        $this->assertSame('SubmitInvoice', $log->payload['operation']);
    }

    #[Test]
    public function reports_failed_soap_responses_only_once_and_throws_soap_fault(): void
    {
        Notification::fake();

        $endpoint = new ApiFixtureSoapEndpoint();
        $client = new ApiFixtureSoapClient([
            new \SoapFault('Server', 'Temporary failure'),
        ]);

        $this->app->bind(SoapApi::class, fn ($app, $params) => new ApiFixtureSoapApi(
            $params['endpoint'],
            $client,
        ));

        try {
            $this->actingAs($this->user);
            (new ApiFixtureSoapAction($endpoint))->handle();
            $this->fail('The action should throw a SoapFault');
        } catch (\SoapFault) {
            $admins = User::active()->admins()->get();

            $this->assertDatabaseCount('api_logs', 1);
            Notification::assertSentTo($admins, ApiCallError::class);
            Notification::assertCount($admins->count());
        }
    }
}
