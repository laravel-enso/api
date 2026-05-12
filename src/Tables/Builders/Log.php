<?php

namespace LaravelEnso\Api\Tables\Builders;

use Illuminate\Database\Eloquent\Builder;
use LaravelEnso\Api\Models\Log as Model;
use LaravelEnso\Tables\Contracts\Table;

class Log implements Table
{
    private const TemplatePath = __DIR__.'/../Templates/apiLogs.json';

    public function query(): Builder
    {
        return Model::with(['user.avatar', 'user.person', 'permission'])
            ->select([
                'id', 'user_id', 'route', 'url', 'method', 'status', 'try',
                'direction', 'duration', 'created_at',
            ]);
    }

    public function templatePath(): string
    {
        return self::TemplatePath;
    }
}
