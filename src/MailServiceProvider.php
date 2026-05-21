<?php

namespace LaravelEnso\Api;

use Illuminate\Support\ServiceProvider;
use LaravelEnso\Mails\Preview\PreviewDefinition;
use LaravelEnso\Mails\Preview\PreviewRegistry;

class MailServiceProvider extends ServiceProvider
{
    public function boot(PreviewRegistry $registry): void
    {
        $registry->register(new PreviewDefinition(
            key: 'api-call-error',
            name: 'API Call Error',
            view: 'laravel-enso/api::emails.api-call-error',
            data: [
                'appellative' => 'Jane',
                'action' => 'sync-products',
                'url' => 'https://api.example.com/products',
                'code' => 422,
                'message' => 'The payload contains an invalid product identifier.',
                'payload' => '{"product_id":"SKU-1001","quantity":12}',
                'triggeredBy' => [
                    'id' => 7,
                    'email' => 'jane@example.com',
                ],
            ],
            section: PreviewDefinition::Core,
        ));
    }
}
