<?php

namespace SavyApps\LaravelStudio\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SavyApps\LaravelStudio\Models\Comment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\SavyApps\LaravelStudio\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = config('studio.authorization.models.user', 'App\\Models\\User');

        return [
            'user_id' => $userModel::factory(),
            'comment' => fake()->sentence(),
            'parent_id' => null,
            // commentable_type and commentable_id should be set when using the factory
        ];
    }

    /**
     * Indicate that the comment is a reply.
     */
    public function reply(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Comment::factory(),
        ]);
    }
}
