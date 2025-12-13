<?php

namespace SavyApps\LaravelStudio\Tests\Feature;

use SavyApps\LaravelStudio\Tests\Fixtures\TestModel;
use SavyApps\LaravelStudio\Tests\TestCase;

class BulkOperationsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Set a lower limit for testing
        $app['config']->set('studio.bulk_operations.max_ids', 5);
    }

    /** @test */
    public function it_validates_bulk_delete_ids_max_limit(): void
    {
        $ids = range(1, 10); // More than max_ids (5)

        $response = $this->postJson('/api/resources/test-resources/bulk/delete', [
            'ids' => $ids,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function it_allows_bulk_delete_within_limit(): void
    {
        $models = TestModel::factory()->count(3)->create();
        $ids = $models->pluck('id')->toArray();

        $response = $this->postJson('/api/resources/test-resources/bulk/delete', [
            'ids' => $ids,
        ]);

        $response->assertOk();

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('test_models', ['id' => $id]);
        }
    }

    /** @test */
    public function it_validates_bulk_update_ids_max_limit(): void
    {
        $ids = range(1, 10); // More than max_ids (5)

        $response = $this->postJson('/api/resources/test-resources/bulk/update', [
            'ids' => $ids,
            'data' => ['status' => 'inactive'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function it_allows_bulk_update_within_limit(): void
    {
        $models = TestModel::factory()->count(3)->create(['status' => 'active']);
        $ids = $models->pluck('id')->toArray();

        $response = $this->postJson('/api/resources/test-resources/bulk/update', [
            'ids' => $ids,
            'data' => ['status' => 'inactive'],
        ]);

        $response->assertOk();

        foreach ($ids as $id) {
            $this->assertDatabaseHas('test_models', ['id' => $id, 'status' => 'inactive']);
        }
    }

    /** @test */
    public function it_validates_action_ids_max_limit(): void
    {
        $ids = range(1, 10); // More than max_ids (5)

        $response = $this->postJson('/api/resources/test-resources/actions/bulk-delete', [
            'ids' => $ids,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function it_validates_ids_are_integers(): void
    {
        $response = $this->postJson('/api/resources/test-resources/bulk/delete', [
            'ids' => ['abc', 'def'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0', 'ids.1']);
    }

    /** @test */
    public function it_validates_ids_are_required(): void
    {
        $response = $this->postJson('/api/resources/test-resources/bulk/delete', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }
}
