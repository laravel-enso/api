<?php

namespace LaravelEnso\Api\Exceptions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use LaravelEnso\Api\Notifications\ApiCallError;
use LaravelEnso\Users\Models\User;

class Handler
{
    public function __construct(
        private string $action,
        private string $url,
        private string|array $body,
        private int|string $code,
        private string $message
    ) {
    }

    public function report(): void
    {
        $notification = $this->notification();

        $this->users()->each->notify($notification);
    }

    private function notification(): ApiCallError
    {
        $args = [
            $this->action, $this->url, $this->body, $this->code, $this->message,
        ];

        return (new ApiCallError(...$args))->onQueue('notifications');
    }

    private function users(): Collection
    {
        $ids = Config::get('enso.api.errorNotificationUserIds');

        return User::active()->when($ids,
            fn ($query) => $query->whereIn('id', $ids),
            fn ($query) => $query->admins(),
        )->get();
    }
}
