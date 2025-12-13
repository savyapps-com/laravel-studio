<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use SavyApps\LaravelStudio\Services\ResourceService;
use SavyApps\LaravelStudio\Tests\Fixtures\TestModel;
use SavyApps\LaravelStudio\Tests\Fixtures\TestResource;
use SavyApps\LaravelStudio\Tests\TestCase;

class ResourceServiceTest extends TestCase
{
    protected ResourceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ResourceService(new TestResource());
    }

    /** @test */
    public function it_sorts_by_allowed_column(): void
    {
        TestModel::create(['name' => 'Charlie', 'email' => 'charlie@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'active']);

        $result = $this->service->index([
            'sort' => 'name',
            'direction' => 'asc',
        ]);

        $names = collect($result->items())->pluck('name')->toArray();
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $names);
    }

    /** @test */
    public function it_falls_back_to_default_sort_for_disallowed_column(): void
    {
        TestModel::create(['name' => 'First', 'email' => 'first@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Second', 'email' => 'second@example.com', 'status' => 'active']);

        // 'age' is not marked as sortable in TestResource
        $result = $this->service->index([
            'sort' => 'age',
            'direction' => 'asc',
        ]);

        // Should fall back to id desc (default), so 'Second' should be first
        $names = collect($result->items())->pluck('name')->toArray();
        $this->assertEquals(['Second', 'First'], $names);
    }

    /** @test */
    public function it_prevents_sql_injection_via_sort_column(): void
    {
        TestModel::create(['name' => 'Test', 'email' => 'test@example.com', 'status' => 'active']);

        // Attempt SQL injection via sort column
        $result = $this->service->index([
            'sort' => '(SELECT password FROM users LIMIT 1)',
            'direction' => 'asc',
        ]);

        // Should not throw an error and fall back to default sort
        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function it_allows_sorting_by_id(): void
    {
        TestModel::create(['name' => 'First', 'email' => 'first@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Second', 'email' => 'second@example.com', 'status' => 'active']);

        $result = $this->service->index([
            'sort' => 'id',
            'direction' => 'asc',
        ]);

        $names = collect($result->items())->pluck('name')->toArray();
        $this->assertEquals(['First', 'Second'], $names);
    }

    /** @test */
    public function it_allows_sorting_by_created_at(): void
    {
        TestModel::create(['name' => 'First', 'email' => 'first@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Second', 'email' => 'second@example.com', 'status' => 'active']);

        $result = $this->service->index([
            'sort' => 'created_at',
            'direction' => 'desc',
        ]);

        // Both created at same time, order might vary
        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_sanitizes_sort_direction(): void
    {
        TestModel::create(['name' => 'First', 'email' => 'first@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Second', 'email' => 'second@example.com', 'status' => 'active']);

        // Invalid direction should be sanitized to 'asc'
        $result = $this->service->index([
            'sort' => 'id',
            'direction' => 'INVALID; DROP TABLE test_models;',
        ]);

        // Should not throw an error
        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function it_applies_search_filters(): void
    {
        TestModel::create(['name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'active']);

        $result = $this->service->index([
            'search' => 'John',
        ]);

        $this->assertCount(1, $result->items());
        $this->assertEquals('John Doe', $result->items()[0]['name']);
    }

    /** @test */
    public function it_applies_filters(): void
    {
        TestModel::create(['name' => 'Active User', 'email' => 'active@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Inactive User', 'email' => 'inactive@example.com', 'status' => 'inactive']);

        $result = $this->service->index([
            'filters' => ['status' => 'active'],
        ]);

        $this->assertCount(1, $result->items());
        $this->assertEquals('active', $result->items()[0]['status']);
    }

    /** @test */
    public function it_creates_resource(): void
    {
        $model = $this->service->store([
            'name' => 'New User',
            'email' => 'new@example.com',
            'status' => 'active',
        ]);

        $this->assertEquals('New User', $model->name);
        $this->assertDatabaseHas('test_models', ['email' => 'new@example.com']);
    }

    /** @test */
    public function it_updates_resource(): void
    {
        $model = TestModel::create(['name' => 'Old', 'email' => 'old@example.com', 'status' => 'active']);

        $updated = $this->service->update($model->id, [
            'name' => 'New Name',
        ]);

        $this->assertEquals('New Name', $updated->name);
    }

    /** @test */
    public function it_deletes_resource(): void
    {
        $model = TestModel::create(['name' => 'Test', 'email' => 'test@example.com', 'status' => 'active']);

        $result = $this->service->destroy($model->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('test_models', ['id' => $model->id]);
    }

    /** @test */
    public function it_bulk_deletes_resources(): void
    {
        $model1 = TestModel::create(['name' => 'Test 1', 'email' => 'test1@example.com', 'status' => 'active']);
        $model2 = TestModel::create(['name' => 'Test 2', 'email' => 'test2@example.com', 'status' => 'active']);

        $count = $this->service->bulkDestroy([$model1->id, $model2->id]);

        $this->assertEquals(2, $count);
        $this->assertDatabaseMissing('test_models', ['id' => $model1->id]);
        $this->assertDatabaseMissing('test_models', ['id' => $model2->id]);
    }

    /** @test */
    public function it_bulk_updates_resources(): void
    {
        $model1 = TestModel::create(['name' => 'Test 1', 'email' => 'test1@example.com', 'status' => 'active']);
        $model2 = TestModel::create(['name' => 'Test 2', 'email' => 'test2@example.com', 'status' => 'active']);

        $count = $this->service->bulkUpdate([$model1->id, $model2->id], ['status' => 'inactive']);

        $this->assertEquals(2, $count);
        $this->assertDatabaseHas('test_models', ['id' => $model1->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('test_models', ['id' => $model2->id, 'status' => 'inactive']);
    }
}
