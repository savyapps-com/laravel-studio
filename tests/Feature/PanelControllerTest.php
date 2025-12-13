<?php

namespace SavyApps\LaravelStudio\Tests\Feature;

use SavyApps\LaravelStudio\Tests\TestCase;

class PanelControllerTest extends TestCase
{
    /** @test */
    public function it_returns_not_found_for_nonexistent_panel(): void
    {
        $response = $this->getJson('/api/panels/nonexistent/info');

        $response->assertStatus(404)
            ->assertJson([
                'error_code' => 'NOT_FOUND',
            ]);
    }

    /** @test */
    public function it_returns_panel_info(): void
    {
        $response = $this->getJson('/api/panels/admin/info');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'panel',
                    'label',
                    'icon',
                ],
            ]);
    }

    /** @test */
    public function it_returns_forbidden_when_user_cannot_access_panel(): void
    {
        $this->actingAsTestUser(['user']);

        $response = $this->getJson('/api/panels/admin');

        $response->assertStatus(403)
            ->assertJson([
                'error_code' => 'FORBIDDEN',
            ]);
    }

    /** @test */
    public function admin_user_can_access_admin_panel(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/panels/admin');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'panel',
                    'label',
                    'path',
                    'icon',
                    'menu',
                    'resources',
                ],
            ]);
    }

    /** @test */
    public function it_returns_panel_menu(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/panels/admin/menu');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['menu'],
            ]);
    }

    /** @test */
    public function it_returns_forbidden_for_menu_without_access(): void
    {
        $this->actingAsTestUser(['user']);

        $response = $this->getJson('/api/panels/admin/menu');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied to this panel',
                'error_code' => 'FORBIDDEN',
            ]);
    }

    /** @test */
    public function it_returns_panel_resources(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->getJson('/api/panels/admin/resources');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['resources'],
            ]);
    }

    /** @test */
    public function it_returns_forbidden_when_switching_to_inaccessible_panel(): void
    {
        $this->actingAsTestUser(['user']);

        $response = $this->postJson('/api/panels/admin/switch');

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
                'error_code',
                'errors' => ['available_panels'],
            ]);
    }

    /** @test */
    public function admin_can_switch_to_admin_panel(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/panels/admin/switch');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'panel',
                    'label',
                    'path',
                    'menu',
                    'resources',
                ],
            ]);
    }
}
