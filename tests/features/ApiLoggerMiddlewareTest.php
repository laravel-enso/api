<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use LaravelEnso\Api\Models\Log;
use LaravelEnso\Api\Notifications\ApiCallError;
use LaravelEnso\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiLoggerMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->user = User::first();

        Route::middleware('api-action-logger')
            ->get('/__api/logger/ok', fn () => response('ok', 200))
            ->name('api.logger.ok');

        Route::middleware('api-action-logger')
            ->post('/__api/logger/created', fn () => response('created', 201))
            ->name('api.logger.created');

        Route::middleware('api-action-logger')
            ->delete('/__api/logger/no-content', fn () => response('', 204))
            ->name('api.logger.no-content');

        Route::middleware('api-action-logger')
            ->get('/__api/logger/redirect', fn () => redirect('/__redirect/target'))
            ->name('api.logger.redirect');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    #[Test]
    public function logs_inbound_request_fields(): void
    {
        $this->actingAs($this->user)
            ->get(route('api.logger.ok', absolute: false))
            ->assertOk();

        $log = Log::latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertSame('api.logger.ok', $log->route);
        $this->assertSame('/__api/logger/ok', parse_url($log->url, PHP_URL_PATH));
        $this->assertSame('GET', $log->method);
        $this->assertSame(200, $log->status);
    }

    #[Test]
    public function does_not_report_200_201_and_204_responses(): void
    {
        Notification::fake();

        $this->actingAs($this->user)->get(route('api.logger.ok', absolute: false))->assertOk();
        $this->actingAs($this->user)->post(route('api.logger.created', absolute: false))->assertCreated();
        $this->actingAs($this->user)->delete(route('api.logger.no-content', absolute: false))->assertNoContent();

        $this->assertDatabaseCount('api_logs', 3);
        Notification::assertNothingSent();
    }

    #[Test]
    public function reports_non_successful_redirect_responses(): void
    {
        Notification::fake();

        $admins = User::active()->admins()->get();

        $this->actingAs($this->user)
            ->get(route('api.logger.redirect', absolute: false))
            ->assertRedirect('/__redirect/target');

        $log = Log::latest()->first();

        $this->assertNotNull($log);
        $this->assertSame(302, $log->status);
        Notification::assertSentTo($admins, ApiCallError::class);
        Notification::assertCount($admins->count());
    }
}
