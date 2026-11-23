<?php

use Illuminate\Support\Env;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApiNotificationConfigTest extends TestCase
{
    #[Test]
    #[DataProvider('recipientLists')]
    public function parses_recipient_ids_from_the_environment(?string $value, array $expected): void
    {
        $key = 'API_ERROR_NOTIFICATION_USER_IDS';
        $repository = Env::getRepository();
        $original = $repository->get($key);

        try {
            $value === null ? $repository->clear($key) : $repository->set($key, $value);
            $config = require __DIR__.'/../../config/api.php';

            $this->assertSame($expected, $config['errorNotificationUserIds']);
        } finally {
            $original === null ? $repository->clear($key) : $repository->set($key, $original);
        }
    }

    public static function recipientLists(): array
    {
        return [
            'unset' => [null, []],
            'empty' => ['', []],
            'whitespace' => ['  ', []],
            'ids' => ['12,34', ['12', '34']],
            'spaces and empty entries' => [' 12, ,34, ', ['12', '34']],
            'zero must not enable admin fallback' => ['0', ['0']],
        ];
    }
}
