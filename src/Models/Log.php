<?php

namespace LaravelEnso\Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelEnso\Api\Enums\Direction;
use LaravelEnso\Api\Enums\Methods;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Rememberable\Traits\Rememberable;
use LaravelEnso\Tables\Traits\TableCache;
use LaravelEnso\Users\Models\User;

class Log extends Model
{
    use Rememberable;
    use TableCache;

    protected $guarded = ['id'];

    protected $table = 'api_logs';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'route', 'name');
    }

    protected function casts(): array
    {
        return [
            'direction' => Direction::class,
            'method' => Methods::class,
            'payload' => 'array',
        ];
    }
}
