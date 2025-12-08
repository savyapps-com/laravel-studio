<?php

namespace SavyApps\LaravelStudio\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'age' => fake()->numberBetween(18, 80),
            'is_active' => fake()->boolean(80),
            'published_at' => fake()->optional()->dateTime(),
        ];
    }
}
