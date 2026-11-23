<?php

require_once __DIR__.'/../Fixtures/ApiTestDoubles.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LaravelEnso\Api\AppServiceProvider;
use LaravelEnso\Api\Exceptions\Handler;
use LaravelEnso\Api\Http\Middleware\ApiLogger;
use LaravelEnso\Api\Notifications\ApiCallError;
use LaravelEnso\Api\SoapApi;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureAction;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureQueryEndpoint;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapAction;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapApi;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapClient;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureSoapEndpoint;
use LaravelEnso\Roles\Enums\Roles;
use LaravelEnso\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiErrorRecipientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed();
        Http::preventStrayRequests();
    }

    #[Test]
    public function notifies_only_configured_active_users_including_non_admins(): void
    {
        $active = User::factory()->create(['is_active' => true, 'role_id' => Roles::Supervisor]);
        $active = User::findOrFail($active->id);
        $inactive = User::factory()->create(['is_active' => false]);
        config(['enso.api.errorNotificationUserIds' => [$active->id, $inactive->id]]);

        (new Handler('Test', 'https://api.test/resource', [], 500, 'Failed'))->report();

        Notification::assertSentTo($active, ApiCallError::class,
            fn ($notification) => $notification->queue === 'notifications');
        Notification::assertNotSentTo($inactive, ApiCallError::class);
        Notification::assertCount(1);
    }

    #[Test]
    public function an_empty_configuration_notifies_all_active_admins(): void
    {
        $this->assertSame([], config('enso.api.errorNotificationUserIds'));
        $admins = User::active()->admins()->get();
        $this->assertNotEmpty($admins);

        (new Handler('Test', 'https://api.test/resource', [], 500, 'Failed'))->report();

        Notification::assertSentTo($admins, ApiCallError::class);
        Notification::assertCount($admins->count());
    }

    #[Test]
    public function inactive_or_missing_ids_do_not_fall_back_to_admins(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $missingId = User::max('id') + 1;
        $this->assertTrue(User::active()->admins()->exists());

        foreach ([[$inactive->id], [$missingId]] as $ids) {
            config(['enso.api.errorNotificationUserIds' => $ids]);

            (new Handler('Test', 'https://api.test/resource', [], 500, 'Failed'))->report();

            Notification::assertNothingSent();
        }
    }

    #[Test]
    public function merges_and_exposes_the_package_configuration_for_publication(): void
    {
        $provider = new AppServiceProvider($this->app);
        config(['enso.api.errorNotificationUserIds' => [42]]);
        $provider->boot();

        $this->assertSame([42], config('enso.api.errorNotificationUserIds'));
        foreach (['api-config', 'enso-config'] as $tag) {
            $paths = ServiceProvider::pathsToPublish(AppServiceProvider::class, $tag);
            $this->assertContains(config_path('enso/api.php'), $paths);
        }
    }

    #[Test]
    public function outbound_failures_and_exceptions_use_the_configured_recipient(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role_id' => Roles::Supervisor]);
        $user = User::findOrFail($user->id);
        config(['enso.api.errorNotificationUserIds' => [$user->id]]);
        Http::fake(['*' => Http::response('Failed', 422)]);

        try {
            (new ApiFixtureAction(new ApiFixtureQueryEndpoint()))->handle();
            $this->fail('Expected a failed response to throw.');
        } catch (RequestException) {
            Notification::assertSentTo($user, ApiCallError::class);
            Notification::assertCount(1);
        }

        Notification::fake();
        Http::fake(fn () => throw new \RuntimeException('Connection failed', 7));

        try {
            (new ApiFixtureAction(new ApiFixtureQueryEndpoint()))->handle();
            $this->fail('Expected the connection exception to be rethrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Connection failed', $exception->getMessage());
            Notification::assertSentTo($user, ApiCallError::class);
            Notification::assertCount(1);
        }
    }

    #[Test]
    public function inbound_failures_use_the_configured_recipient(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role_id' => Roles::Supervisor]);
        $user = User::findOrFail($user->id);
        config(['enso.api.errorNotificationUserIds' => [$user->id]]);
        Route::middleware(ApiLogger::class)
            ->domain('api-recipients.test')
            ->get('/__api/recipients-error', fn () => response('Failed', 500))
            ->name('api.recipients-error');
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();

        $this->get(route('api.recipients-error'))->assertStatus(500);

        Notification::assertSentTo($user, ApiCallError::class);
        Notification::assertCount(1);
    }

    #[Test]
    public function soap_failures_use_the_configured_recipient_once(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role_id' => Roles::Supervisor]);
        $user = User::findOrFail($user->id);
        config(['enso.api.errorNotificationUserIds' => [$user->id]]);
        $client = new ApiFixtureSoapClient([new \SoapFault('Server', 'Temporary failure')]);
        $this->app->bind(SoapApi::class, fn ($app, $params) => new ApiFixtureSoapApi(
            $params['endpoint'], $client,
        ));

        try {
            (new ApiFixtureSoapAction(new ApiFixtureSoapEndpoint()))->handle();
            $this->fail('Expected the SOAP fault to be rethrown.');
        } catch (\SoapFault $exception) {
            $this->assertSame('Temporary failure', $exception->getMessage());
            Notification::assertSentTo($user, ApiCallError::class);
            Notification::assertCount(1);
        }
    }
}
