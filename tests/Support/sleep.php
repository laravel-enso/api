<?php

namespace LaravelEnso\Api\Tests\Support;

class ApiSleepRecorder
{
    public static array $calls = [];

    public static function reset(): void
    {
        self::$calls = [];
    }

    public static function record(int $seconds): void
    {
        self::$calls[] = $seconds;
    }
}

namespace LaravelEnso\Api;

use LaravelEnso\Api\Tests\Support\ApiSleepRecorder;

if (! function_exists(__NAMESPACE__.'\sleep')) {
    function sleep(int $seconds): int
    {
        ApiSleepRecorder::record($seconds);

        return 0;
    }
}
