<?php

declare(strict_types=1);

namespace Database\Factories\Post;

use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * This is specifically testing a factory
 * that is not in the expected namespace.
 *
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'user_id' => User::factory(),
        ];
    }
}
