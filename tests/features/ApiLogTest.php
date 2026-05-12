<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\Api\Enums\Direction;
use LaravelEnso\Api\Enums\Methods;
use LaravelEnso\Api\Models\Log;
use LaravelEnso\Menus\Models\Menu;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Tables\Traits\Tests\Datatable;
use LaravelEnso\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiLogTest extends TestCase
{
    use RefreshDatabase;
    use Datatable {
        can_view_index as private canViewApiLogsTable;
    }

    private const Route = 'administration.users.show';

    private string $permissionGroup = 'system.apiLogs';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->user = User::first();
    }

    #[Test]
    public function can_view_index(): void
    {
        $this->log([
            'user_id' => $this->user->id,
            'route' => self::Route,
        ]);

        $this->actingAs($this->user);

        $this->canViewApiLogsTable();
    }

    #[Test]
    public function filters_api_logs_by_user(): void
    {
        $other = User::whereKeyNot($this->user->id)->firstOrFail();

        $this->log(['user_id' => $this->user->id, 'url' => 'https://api.test/user']);
        $this->log(['user_id' => $other->id, 'url' => 'https://api.test/other']);

        $params = $this->tableParams([
            'filters' => [
                'api_logs' => [
                    'user_id' => $this->user->id,
                ],
            ],
        ]);

        $this->actingAs($this->user)
            ->get(route('system.apiLogs.tableData', $params, false))
            ->assertStatus(200)
            ->assertJsonFragment(['url' => 'https://api.test/user'])
            ->assertJsonMissing(['url' => 'https://api.test/other']);
    }

    #[Test]
    public function filters_api_logs_by_permission(): void
    {
        $this->log(['route' => self::Route, 'url' => 'https://api.test/permission']);
        $this->log(['route' => 'core.home.index', 'url' => 'https://api.test/home']);

        $params = $this->tableParams([
            'filters' => [
                'api_logs' => [
                    'route' => self::Route,
                ],
            ],
        ]);

        $this->actingAs($this->user)
            ->get(route('system.apiLogs.tableData', $params, false))
            ->assertStatus(200)
            ->assertJsonFragment(['url' => 'https://api.test/permission'])
            ->assertJsonMissing(['url' => 'https://api.test/home']);
    }

    #[Test]
    public function filters_api_logs_by_method_direction_and_created_at_interval(): void
    {
        $this->log([
            'method' => Methods::POST,
            'direction' => Direction::Outbound,
            'url' => 'https://api.test/current',
            'created_at' => Carbon::parse('2026-05-12 10:00:00'),
        ]);

        $this->log([
            'method' => Methods::GET,
            'direction' => Direction::Inbound,
            'url' => 'https://api.test/old',
            'created_at' => Carbon::parse('2026-05-10 10:00:00'),
        ]);

        $params = $this->tableParams([
            'filters' => [
                'api_logs' => [
                    'method' => Methods::POST->value,
                    'direction' => Direction::Outbound->value,
                ],
            ],
            'intervals' => [
                'api_logs' => [
                    'created_at' => [
                        'min' => '2026-05-12 00:00:00',
                        'max' => '2026-05-12 23:59:59',
                    ],
                ],
            ],
        ]);

        $this->actingAs($this->user)
            ->get(route('system.apiLogs.tableData', $params, false))
            ->assertStatus(200)
            ->assertJsonFragment(['url' => 'https://api.test/current'])
            ->assertJsonMissing(['url' => 'https://api.test/old']);
    }

    #[Test]
    public function creates_api_logs_structure(): void
    {
        $this->assertTrue(Permission::whereName('system.apiLogs.index')->exists());
        $this->assertTrue(Permission::whereName('system.apiLogs.initTable')->exists());
        $this->assertTrue(Permission::whereName('system.apiLogs.tableData')->exists());
        $this->assertTrue(Permission::whereName('system.apiLogs.exportExcel')->exists());

        $this->assertTrue(Menu::whereName('API Logs')
            ->whereHas('permission', fn ($query) => $query
                ->whereName('system.apiLogs.index'))
            ->exists());
    }

    private function log(array $attributes = []): Log
    {
        return Log::create($attributes + [
            'user_id' => $this->user->id,
            'route' => null,
            'url' => 'https://api.test/log',
            'method' => Methods::GET,
            'status' => 200,
            'try' => 1,
            'direction' => Direction::Inbound,
            'duration' => 0.12,
        ]);
    }

    private function tableParams(array $params = []): array
    {
        return [
            'columns' => [],
            'meta' => '{"start":0,"length":10,"sort":false,"search":"","forceInfo":false,"searchMode":"full"}',
            'filters' => json_encode($params['filters'] ?? [], JSON_THROW_ON_ERROR),
            'intervals' => json_encode($params['intervals'] ?? [], JSON_THROW_ON_ERROR),
        ];
    }
}
