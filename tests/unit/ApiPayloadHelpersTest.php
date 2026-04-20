<?php

require_once __DIR__.'/../Fixtures/ApiTestDoubles.php';

use Illuminate\Support\Collection;
use LaravelEnso\Api\Filter;
use LaravelEnso\Api\Exceptions\Argument;
use LaravelEnso\Api\Exceptions\Filters;
use LaravelEnso\Api\Tests\Fixtures\ApiFixtureResource;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiPayloadHelpersTest extends TestCase
{
    #[Test]
    public function rejects_invalid_filters(): void
    {
        $this->expectException(Filters::class);
        $this->expectExceptionMessage('Invalid filter(s) "unsupported"');

        new class (['supported' => 1, 'unsupported' => 2]) extends Filter {
            public function allowed(): array
            {
                return ['supported'];
            }
        }->toArray();
    }

    #[Test]
    public function resolves_nested_resources_and_collections_to_arrays(): void
    {
        $payload = [
            'id' => 1,
            'name' => 'Offer',
            'nested' => ['supplier' => 'Enso'],
        ];

        $resolved = (new ApiFixtureResource($payload))->resolve();
        $collection = ApiFixtureResource::collection(Collection::wrap([$payload]));

        $this->assertSame([
            'id' => 1,
            'name' => 'Offer',
            'nested' => ['supplier' => 'Enso'],
        ], $resolved);

        $this->assertSame([$resolved], $collection);
    }

    #[Test]
    public function validates_mandatory_attributes(): void
    {
        $this->expectException(Argument::class);
        $this->expectExceptionMessage('Mandatory attribute(s) missing "name"');

        new class extends \LaravelEnso\Api\Resource {
            public function toArray(): array
            {
                return ['id' => 1];
            }

            protected function mandatoryAttributes(): array
            {
                return ['id', 'name'];
            }
        }->resolve();
    }

    #[Test]
    public function serializes_resources_to_json(): void
    {
        $json = (new ApiFixtureResource([
            'id' => 1,
            'name' => 'Offer',
            'nested' => ['supplier' => 'Enso'],
        ]))->toJson();

        $this->assertJson($json);
        $this->assertSame('Offer', json_decode($json, true)['name']);
    }
}
