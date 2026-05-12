<?php

namespace LaravelEnso\Api\Upgrades;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Contracts\MigratesTable;
use LaravelEnso\Upgrade\Helpers\Column;
use LaravelEnso\Upgrade\Helpers\Table;

class ApiLogMethod implements MigratesTable
{
    public function isMigrated(): bool
    {
        return Table::exists('api_logs')
            && Table::hasColumn('api_logs', 'method')
            && ! Column::isString('api_logs', 'method');
    }

    public function migrateTable(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('method_int')->nullable()->after('method');
        });

        DB::table('api_logs')->update([
            'method_int' => DB::raw("CASE UPPER(method)
                WHEN 'GET' THEN 1
                WHEN 'POST' THEN 2
                WHEN 'PUT' THEN 3
                WHEN 'PATCH' THEN 4
                WHEN 'DELETE' THEN 5
                WHEN 'OPTIONS' THEN 6
                WHEN 'HEAD' THEN 7
                ELSE NULL
            END"),
        ]);

        DB::table('api_logs')->whereNull('method_int')->update(['method_int' => 1]);

        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropColumn('method');
        });

        Schema::table('api_logs', function (Blueprint $table) {
            $table->renameColumn('method_int', 'method');
        });

        Schema::table('api_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('method')->nullable(false)->change();
        });

        if (! Table::hasIndex('api_logs', 'api_logs_method_index')) {
            Schema::table('api_logs', fn (Blueprint $table) => $table->index('method'));
        }

        if (! Table::hasIndex('api_logs', 'api_logs_user_id_created_at_index')) {
            Schema::table('api_logs', fn (Blueprint $table) => $table->index(['user_id', 'created_at']));
        }

        if (! Table::hasIndex('api_logs', 'api_logs_method_created_at_index')) {
            Schema::table('api_logs', fn (Blueprint $table) => $table->index(['method', 'created_at']));
        }
    }
}
