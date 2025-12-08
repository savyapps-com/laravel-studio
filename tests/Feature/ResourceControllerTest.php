<?php

namespace SavyApps\LaravelStudio\Tests\Feature;

use SavyApps\LaravelStudio\Tests\TestCase;
use SavyApps\LaravelStudio\Tests\Fixtures\TestModel;

class ResourceControllerTest extends TestCase
{
    /** @test */
    public function it_returns_resource_metadata(): void
    {
        $response = $this->getJson('/api/resources/test-resources/meta');

        $response->assertOk()
            ->assertJsonStructure([
                'fields',
                'filters',
                'actions',
            ]);
    }

    /** @test */
    public function it_lists_resources(): void
    {
        TestModel::factory()->count(5)->create();

        $response = $this->getJson('/api/resources/test-resources');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'status'],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_shows_single_resource(): void
    {
        $model = TestModel::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/resources/test-resources/{$model->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $model->id,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
    }

    /** @test */
    public function it_creates_resource(): void
    {
        $data = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'status' => 'active',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/resources/test-resources', $data);

        $response->assertCreated()
            ->assertJson([
                'name' => 'New User',
                'email' => 'new@example.com',
            ]);

        $this->assertDatabaseHas('test_models', [
            'name' => 'New User',
            'email' => 'new@example.com',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_create(): void
    {
        $response = $this->postJson('/api/resources/test-resources', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    /** @test */
    public function it_updates_resource(): void
    {
        $model = TestModel::create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'status' => 'active',
        ]);

        $response = $this->putJson("/api/resources/test-resources/{$model->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => 'inactive',
        ]);

        $response->assertOk()
            ->assertJson([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $this->assertDatabaseHas('test_models', [
            'id' => $model->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_patches_resource(): void
    {
        $model = TestModel::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $response = $this->patchJson("/api/resources/test-resources/{$model->id}", [
            'status' => 'inactive',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('test_models', [
            'id' => $model->id,
            'status' => 'inactive',
        ]);
    }

    /** @test */
    public function it_deletes_resource(): void
    {
        $model = TestModel::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/resources/test-resources/{$model->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('test_models', [
            'id' => $model->id,
        ]);
    }

    /** @test */
    public function it_bulk_deletes_resources(): void
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
    public function it_searches_resources(): void
    {
        TestModel::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);

        TestModel::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/resources/test-resources?search=John');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('John Doe', $data[0]['name']);
    }

    /** @test */
    public function it_sorts_resources(): void
    {
        TestModel::create(['name' => 'Charlie', 'email' => 'charlie@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'active']);

        $response = $this->getJson('/api/resources/test-resources?sortBy=name&sortDirection=asc');

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $names);
    }

    /** @test */
    public function it_filters_resources(): void
    {
        TestModel::create(['name' => 'Active User', 'email' => 'active@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Inactive User', 'email' => 'inactive@example.com', 'status' => 'inactive']);

        $response = $this->getJson('/api/resources/test-resources?filters[status]=active');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('active', $data[0]['status']);
    }

    /** @test */
    public function it_paginates_resources(): void
    {
        TestModel::factory()->count(25)->create();

        $response = $this->getJson('/api/resources/test-resources?perPage=10');

        $response->assertOk();

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_resource(): void
    {
        $response = $this->getJson('/api/resources/nonexistent-resource/meta');

        $response->assertNotFound();
    }
}
