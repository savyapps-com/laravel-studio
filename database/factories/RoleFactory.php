<?php

namespace SavyApps\LaravelStudio\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SavyApps\LaravelStudio\Models\Role;

/**
 * Factory for creating Role model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\SavyApps\LaravelStudio\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\SavyApps\LaravelStudio\Models\Role>
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name, '_'),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the role is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Super administrator with unrestricted access - bypasses all permission checks',
        ]);
    }

    /**
     * Indicate that the role is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrator with full access to all features',
        ]);
    }

    /**
     * Indicate that the role is a regular user.
     */
    public function user(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Regular user with standard access',
        ]);
    }
}
