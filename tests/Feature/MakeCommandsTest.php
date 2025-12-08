<?php

namespace SavyApps\LaravelStudio\Tests\Feature;

use SavyApps\LaravelStudio\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeCommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up generated files
        $this->cleanupGeneratedFiles();

        parent::tearDown();
    }

    /** @test */
    public function make_resource_command_creates_resource_file(): void
    {
        $this->artisan('studio:make-resource', ['name' => 'TestProduct'])
            ->assertSuccessful();

        $this->assertFileExists(app_path('Resources/TestProductResource.php'));
    }

    /** @test */
    public function make_resource_command_uses_custom_model_name(): void
    {
        $this->artisan('studio:make-resource', [
            'name' => 'Product',
            '--model' => 'CustomProduct',
        ])->assertSuccessful();

        $content = File::get(app_path('Resources/ProductResource.php'));
        $this->assertStringContainsString('CustomProduct::class', $content);
    }

    /** @test */
    public function make_resource_command_fails_if_file_exists(): void
    {
        // Create file first
        $this->artisan('studio:make-resource', ['name' => 'Product'])
            ->assertSuccessful();

        // Try to create again
        $this->artisan('studio:make-resource', ['name' => 'Product'])
            ->assertFailed();
    }

    /** @test */
    public function make_resource_command_overwrites_with_force_flag(): void
    {
        // Create file first
        $this->artisan('studio:make-resource', ['name' => 'Product'])
            ->assertSuccessful();

        // Overwrite with --force
        $this->artisan('studio:make-resource', [
            'name' => 'Product',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists(app_path('Resources/ProductResource.php'));
    }

    /** @test */
    public function make_filter_command_creates_filter_file(): void
    {
        $this->artisan('studio:make-filter', ['name' => 'Status'])
            ->assertSuccessful();

        $this->assertFileExists(app_path('Resources/Filters/StatusFilter.php'));
    }

    /** @test */
    public function make_filter_command_creates_directory_if_not_exists(): void
    {
        $this->assertDirectoryDoesNotExist(app_path('Resources/Filters'));

        $this->artisan('studio:make-filter', ['name' => 'Status'])
            ->assertSuccessful();

        $this->assertDirectoryExists(app_path('Resources/Filters'));
    }

    /** @test */
    public function make_action_command_creates_action_file(): void
    {
        $this->artisan('studio:make-action', ['name' => 'Archive'])
            ->assertSuccessful();

        $this->assertFileExists(app_path('Resources/Actions/ArchiveAction.php'));
    }

    /** @test */
    public function make_action_command_creates_directory_if_not_exists(): void
    {
        $this->assertDirectoryDoesNotExist(app_path('Resources/Actions'));

        $this->artisan('studio:make-action', ['name' => 'Archive'])
            ->assertSuccessful();

        $this->assertDirectoryExists(app_path('Resources/Actions'));
    }

    /**
     * Clean up generated test files.
     */
    protected function cleanupGeneratedFiles(): void
    {
        $files = [
            app_path('Resources/TestProductResource.php'),
            app_path('Resources/ProductResource.php'),
            app_path('Resources/Filters/StatusFilter.php'),
            app_path('Resources/Actions/ArchiveAction.php'),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        $directories = [
            app_path('Resources/Filters'),
            app_path('Resources/Actions'),
        ];

        foreach ($directories as $directory) {
            if (File::isDirectory($directory) && count(File::files($directory)) === 0) {
                File::deleteDirectory($directory);
            }
        }
    }
}
