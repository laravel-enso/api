<?php

namespace LaravelEnso\Api\Http\Controllers\Log;

use Illuminate\Routing\Controller;
use LaravelEnso\Api\Tables\Builders\Log;
use LaravelEnso\Tables\Traits\Init;

class InitTable extends Controller
{
    use Init;

    protected string $tableClass = Log::class;
}
