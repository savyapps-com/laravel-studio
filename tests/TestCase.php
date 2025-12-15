<?php

namespace SavyApps\LaravelStudio\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase as Orchestra;
use SavyApps\LaravelStudio\LaravelStudioServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelStudioServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure auth guards - sanctum uses session driver for testing
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => TestUser::class,
        ]);

        // Setup Laravel Studio config
        $app['config']->set('studio.resources', [
            'test-resources' => [
                'class' => \SavyApps\LaravelStudio\Tests\Fixtures\TestResource::class,
                'label' => 'Test Resources',
                'icon' => 'users',
            ],
        ]);

        $app['config']->set('studio.middleware', ['api']);
        $app['config']->set('studio.prefix', 'api/resources');
        $app['config']->set('studio.name_prefix', 'api.resources.');

        // Setup panels config
        $app['config']->set('studio.panels', [
            'admin' => [
                'label' => 'Admin Panel',
                'path' => '/admin',
                'icon' => 'layout',
                'role' => 'admin',
                'resources' => ['test-resources'],
                'features' => [],
                'menu' => [
                    ['type' => 'link', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
                ],
            ],
        ]);

        // Setup bulk operations config
        $app['config']->set('studio.bulk_operations', [
            'max_ids' => 1000,
            'chunk_size' => 100,
        ]);

        // Setup authorization config
        $app['config']->set('studio.authorization', [
            'enabled' => false, // Disable authorization in tests to avoid gate registration
            'super_admin_role' => 'super_admin',
        ]);

        // Setup unified cache config (disabled for tests)
        $app['config']->set('studio.cache', [
            'enabled' => false,
            'ttl' => 3600,
            'prefix' => 'studio_test_',
        ]);

        // Setup global search config
        $app['config']->set('studio.global_search', [
            'enabled' => true,
            'min_characters' => 2,
            'max_results' => 20,
        ]);

        // Setup cards config
        $app['config']->set('studio.cards', [
            'enabled' => true,
            'cache_ttl' => 300,
        ]);
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }

    /**
     * Get test model.
     */
    protected function getTestModel(): string
    {
        return \SavyApps\LaravelStudio\Tests\Fixtures\TestModel::class;
    }

    /**
     * Get test resource.
     */
    protected function getTestResource(): string
    {
        return \SavyApps\LaravelStudio\Tests\Fixtures\TestResource::class;
    }

    /**
     * Create a test user with optional roles.
     */
    protected function createTestUser(array $attributes = [], array $roles = []): TestUser
    {
        $user = new TestUser(array_merge([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ], $attributes));

        $user->setRoles($roles);

        return $user;
    }

    /**
     * Acting as a test user with optional roles.
     */
    protected function actingAsTestUser(array $roles = []): static
    {
        $user = $this->createTestUser([], $roles);
        $this->actingAs($user);

        return $this;
    }

    /**
     * Define a gate for testing.
     */
    protected function defineGate(string $ability, bool $allowed = true): void
    {
        Gate::define($ability, fn () => $allowed);
    }
}

/**
 * Test user class for authentication testing.
 */
class TestUser extends Authenticatable
{
    protected $fillable = ['id', 'name', 'email'];

    protected array $roles = [];

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function hasPermission(string $permission): bool
    {
        return Gate::allows($permission);
    }
}
