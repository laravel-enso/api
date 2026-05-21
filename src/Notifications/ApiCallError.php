<?php

namespace LaravelEnso\Api\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class ApiCallError extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $action,
        private string $url,
        private string|array $payload,
        private int|string $code,
        private string $message,
    ) {
    }

    public function via()
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $app = Config::get('app.name');

        return (new MailMessage())
            ->subject("[ {$app} ] {$this->subject()}")
            ->markdown('laravel-enso/api::emails.api-call-error', [
                'appellative' => $notifiable->person->appellative(),
                'action' => $this->action,
                'url' => $this->url,
                'code' => $this->code,
                'message' => $this->message,
                'payload' => json_encode($this->payload),
                'triggeredBy' => Auth::check()
                    ? [
                        'id' => Auth::id(),
                        'email' => Auth::user()->email,
                    ]
                    : null,
            ]);
    }

    private function subject(): string
    {
        return __('API call for :action failed', [
            'action' => $this->action,
        ]);
    }
}
