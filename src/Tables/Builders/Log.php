<?php

namespace LaravelEnso\Api\Tables\Builders;

use Illuminate\Database\Eloquent\Builder;
use LaravelEnso\Api\Models\Log as Model;
use LaravelEnso\Helpers\Services\Obj;
use LaravelEnso\Tables\Contracts\CustomFilter;
use LaravelEnso\Tables\Contracts\Table;

class Log implements Table, CustomFilter
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

    public function filterApplies(Obj $params): bool
    {
        return $params->filled('roleId');
    }

    public function filter(Builder $query, Obj $params): void
    {
        $query->whereHas('user', fn (Builder $user) => $user
            ->where('role_id', $params->get('roleId')));
    }

    public function templatePath(): string
    {
        return self::TemplatePath;
    }
}
