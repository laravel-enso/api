<?php

namespace LaravelEnso\Api\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelEnso\Rememberable\Traits\Rememberable;
use LaravelEnso\Tables\Traits\TableCache;
use LaravelEnso\Users\Models\User;

class Log extends Model
{
    use Rememberable;
    use TableCache;

    protected $guarded = ['id'];

    protected $table = 'api_logs';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
