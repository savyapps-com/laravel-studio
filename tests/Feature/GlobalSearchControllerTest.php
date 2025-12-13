<?php

namespace SavyApps\LaravelStudio\Tests\Feature;

use SavyApps\LaravelStudio\Tests\TestCase;

class GlobalSearchControllerTest extends TestCase
{
    /** @test */
    public function it_validates_search_query_is_required(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_validates_search_query_max_length(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/search?q=' . str_repeat('a', 300));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_validates_panel_max_length(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/search?q=test&panel=' . str_repeat('a', 150));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['panel']);
    }

    /** @test */
    public function it_returns_unauthorized_when_clearing_recent_without_auth(): void
    {
        $response = $this->deleteJson('/api/search/recent');

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_clear_recent_searches(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->deleteJson('/api/search/recent');

        $response->assertOk()
            ->assertJson([
                'message' => 'Recent searches cleared',
            ]);
    }

    /** @test */
    public function it_returns_searchable_resources(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/search/resources');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['resources'],
            ]);
    }

    /** @test */
    public function it_returns_suggestions(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/search/suggestions');

        $response->assertOk();
    }

    /** @test */
    public function it_performs_global_search(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/search?q=test');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['results'],
            ]);
    }
}
