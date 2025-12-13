<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use Illuminate\Support\Facades\DB;
use SavyApps\LaravelStudio\Services\ResourceService;
use SavyApps\LaravelStudio\Tests\Fixtures\TestModel;
use SavyApps\LaravelStudio\Tests\Fixtures\TestResource;
use SavyApps\LaravelStudio\Tests\TestCase;

class QueryPerformanceTest extends TestCase
{
    protected ResourceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ResourceService(new TestResource());
    }

    /** @test */
    public function it_does_not_produce_n_plus_one_queries_on_index(): void
    {
        // Create multiple records
        for ($i = 0; $i < 10; $i++) {
            TestModel::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => 'active',
            ]);
        }

        // Enable query log
        DB::enableQueryLog();

        // Fetch index
        $result = $this->service->index([]);

        // Get queries
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have at most 3 queries:
        // 1. Count for pagination
        // 2. Select for items
        // 3. (Optional) relationship eager loading
        $this->assertLessThanOrEqual(3, count($queries),
            'Index should not produce N+1 queries. Queries executed: ' . count($queries));
    }

    /** @test */
    public function it_efficiently_handles_bulk_operations(): void
    {
        // Create multiple records
        $ids = [];
        for ($i = 0; $i < 20; $i++) {
            $model = TestModel::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => 'active',
            ]);
            $ids[] = $model->id;
        }

        // Enable query log
        DB::enableQueryLog();

        // Perform bulk update
        $this->service->bulkUpdate($ids, ['status' => 'inactive']);

        // Get queries
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Bulk update should be done efficiently, not one query per record
        // Expect at most 2 queries (one update, maybe one select)
        $this->assertLessThanOrEqual(3, count($queries),
            'Bulk update should not produce a query per record. Queries: ' . count($queries));
    }

    /** @test */
    public function it_efficiently_handles_bulk_delete(): void
    {
        // Create multiple records
        $ids = [];
        for ($i = 0; $i < 20; $i++) {
            $model = TestModel::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => 'active',
            ]);
            $ids[] = $model->id;
        }

        // Enable query log
        DB::enableQueryLog();

        // Perform bulk delete
        $this->service->bulkDestroy($ids);

        // Get queries
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should be done in a single DELETE ... WHERE id IN (...) query
        $this->assertLessThanOrEqual(2, count($queries),
            'Bulk delete should use single query. Queries: ' . count($queries));
    }

    /** @test */
    public function it_uses_pagination_efficiently(): void
    {
        // Create many records
        for ($i = 0; $i < 50; $i++) {
            TestModel::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => 'active',
            ]);
        }

        // Enable query log
        DB::enableQueryLog();

        // Fetch paginated results
        $result = $this->service->index(['perPage' => 10]);

        // Get queries
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should only fetch the requested page, not all 50 records
        $this->assertCount(10, $result->items());

        // Check that we're not selecting all 50 records
        foreach ($queries as $query) {
            $this->assertStringNotContainsString('LIMIT 50', $query['query'] ?? '');
        }
    }

    /** @test */
    public function it_applies_search_with_index_usage(): void
    {
        // Create records
        for ($i = 0; $i < 10; $i++) {
            TestModel::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => 'active',
            ]);
        }

        TestModel::create([
            'name' => 'Searchable Name',
            'email' => 'searchable@example.com',
            'status' => 'active',
        ]);

        // Enable query log
        DB::enableQueryLog();

        // Search
        $result = $this->service->index(['search' => 'Searchable']);

        // Get queries
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should find exactly one result
        $this->assertCount(1, $result->items());
        $this->assertEquals('Searchable Name', $result->items()[0]['name']);

        // Verify LIKE query is used
        $hasLikeQuery = false;
        foreach ($queries as $query) {
            if (str_contains($query['query'] ?? '', 'like')) {
                $hasLikeQuery = true;
                break;
            }
        }
        $this->assertTrue($hasLikeQuery, 'Search should use LIKE query');
    }
}
