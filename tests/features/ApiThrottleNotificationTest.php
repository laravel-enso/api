<?php

require_once __DIR__.'/../Support/sleep.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use LaravelEnso\Api\Exceptions\Handler;
use LaravelEnso\Api\Notifications\ApiCallError;
use LaravelEnso\Api\Throttle;
use LaravelEnso\Api\Tests\Support\ApiSleepRecorder;
use LaravelEnso\Roles\Enums\Roles;
use LaravelEnso\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiThrottleNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        ApiSleepRecorder::reset();
    }

    #[Test]
    public function uses_second_based_sleep_for_throttle_debounce(): void
    {
        $throttle = new Throttle(1);
        $time = new \ReflectionProperty(Throttle::class, 'time');
        $time->setAccessible(true);
        $time->setValue($throttle, time() + 1);

        $this->assertSame($throttle, $throttle());
        $this->assertSame([2], ApiSleepRecorder::$calls);
    }

    #[Test]
    public function builds_api_error_mail_messages_with_triggering_user_context(): void
    {
        $triggerUser = User::first();
        $admin = User::query()->whereRoleId(Roles::Admin)->where('is_active', true)->first();

        Auth::guard('web')->login($triggerUser);

        $mail = (new ApiCallError(
            'FetchOffersAction',
            'https://api.test/offers',
            ['query' => 'offers'],
            422,
            'Validation failed',
        ))->toMail($admin);

        $this->assertStringContainsString(config('app.name'), $mail->subject);
        $this->assertStringContainsString('FetchOffersAction', $mail->subject);
        $this->assertStringContainsString($admin->person->appellative(), $mail->greeting);
        $this->assertStringContainsString('Validation failed', implode("\n", $mail->introLines));
        $this->assertStringContainsString($triggerUser->email, implode("\n", $mail->introLines));
    }

    #[Test]
    public function notifies_only_active_admins(): void
    {
        Notification::fake();

        $activeAdmin = User::query()->whereRoleId(Roles::Admin)->where('is_active', true)->first();
        $inactiveAdmin = User::factory()->create([
            'role_id' => Roles::Admin,
            'is_active' => false,
        ]);

        (new Handler(
            'FetchOffersAction',
            'https://api.test/offers',
            ['query' => 'offers'],
            500,
            'Boom',
        ))->report();

        Notification::assertSentTo($activeAdmin, ApiCallError::class, function ($notification) {
            return $notification->queue === 'notifications';
        });
        Notification::assertNotSentTo($inactiveAdmin, ApiCallError::class);
    }
}
