<?php

namespace SavyApps\LaravelStudio\Tests\Feature;

use Illuminate\Support\Facades\Route;
use SavyApps\LaravelStudio\Tests\TestCase;

class PanelManagementSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define admin middleware for testing (noop in tests)
        Route::aliasMiddleware('admin', \Illuminate\Auth\Middleware\Authenticate::class);
    }

    /** @test */
    public function it_validates_path_format_on_create(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test-panel',
            'label' => 'Test Panel',
            'path' => '/../../etc/passwd',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    /** @test */
    public function it_accepts_valid_path_on_create(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test-panel',
            'label' => 'Test Panel',
            'path' => '/admin/dashboard',
        ]);

        // Path validation should pass (may still fail due to other issues like db)
        $errors = $response->json('errors', []);
        $this->assertArrayNotHasKey('path', $errors);
    }

    /** @test */
    public function it_rejects_path_with_special_characters(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test-panel',
            'label' => 'Test Panel',
            'path' => '/admin<script>alert(1)</script>',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    /** @test */
    public function it_rejects_path_with_spaces(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test-panel',
            'label' => 'Test Panel',
            'path' => '/admin panel',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    /** @test */
    public function it_accepts_path_with_hyphens(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test-panel',
            'label' => 'Test Panel',
            'path' => '/admin-panel',
        ]);

        // Path validation should pass
        $errors = $response->json('errors', []);
        $this->assertArrayNotHasKey('path', $errors);
    }

    /** @test */
    public function it_accepts_path_with_numbers(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test-panel',
            'label' => 'Test Panel',
            'path' => '/admin123',
        ]);

        // Path validation should pass
        $errors = $response->json('errors', []);
        $this->assertArrayNotHasKey('path', $errors);
    }

    /** @test */
    public function it_rejects_path_not_starting_with_slash(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test-panel',
            'label' => 'Test Panel',
            'path' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    /** @test */
    public function it_validates_key_is_alpha_dash(): void
    {
        $this->actingAsTestUser(['admin']);

        $response = $this->postJson('/api/admin/panel-management', [
            'key' => 'test panel with spaces',
            'label' => 'Test Panel',
            'path' => '/test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }
}
