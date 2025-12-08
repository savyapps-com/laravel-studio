<?php

namespace SavyApps\LaravelStudio\Tests;

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

        // Setup Laravel Studio config
        $app['config']->set('studio.resources', [
            'test-resources' => \SavyApps\LaravelStudio\Tests\Fixtures\TestResource::class,
        ]);

        $app['config']->set('studio.middleware', ['api']);
        $app['config']->set('studio.prefix', 'api/resources');
        $app['config']->set('studio.name_prefix', 'api.resources.');
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
}
