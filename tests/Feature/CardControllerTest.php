<?php

namespace SavyApps\LaravelStudio\Tests\Feature;

use SavyApps\LaravelStudio\Tests\TestCase;

class CardControllerTest extends TestCase
{
    /** @test */
    public function it_returns_unauthorized_when_clearing_all_caches_without_auth(): void
    {
        $response = $this->deleteJson('/api/cards/cache');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_forbidden_when_user_lacks_permission_to_clear_all_caches(): void
    {
        $this->actingAsTestUser(['user']);

        $response = $this->deleteJson('/api/cards/cache');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized to clear all card caches',
                'error_code' => 'FORBIDDEN',
            ]);
    }

    /** @test */
    public function super_admin_can_clear_all_caches(): void
    {
        $this->actingAsTestUser(['super_admin']);

        $response = $this->deleteJson('/api/cards/cache');

        $response->assertOk()
            ->assertJson([
                'message' => 'All card caches cleared',
            ]);
    }

    /** @test */
    public function user_with_manage_cards_permission_can_clear_all_caches(): void
    {
        $this->actingAsTestUser(['user']);
        $this->defineGate('manage-cards', true);

        $response = $this->deleteJson('/api/cards/cache');

        $response->assertOk()
            ->assertJson([
                'message' => 'All card caches cleared',
            ]);
    }

    /** @test */
    public function it_returns_not_found_for_nonexistent_card(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/cards/test-resources/nonexistent-card');

        $response->assertStatus(404)
            ->assertJson([
                'error_code' => 'NOT_FOUND',
            ]);
    }

    /** @test */
    public function it_returns_card_types(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/cards/types');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['types'],
            ]);
    }

    /** @test */
    public function it_clears_resource_card_cache(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->deleteJson('/api/cards/test-resources/cache');

        $response->assertOk()
            ->assertJson([
                'message' => 'Card cache cleared',
                'data' => [
                    'resource' => 'test-resources',
                ],
            ]);
    }

    /** @test */
    public function it_returns_dashboard_cards(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/cards/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => ['total'],
            ]);
    }

    /** @test */
    public function it_returns_resource_cards(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/cards/test-resources');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => ['resource', 'total'],
            ]);
    }
}
