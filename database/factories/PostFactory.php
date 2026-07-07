<?php

namespace Database\Factories;

use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'content'    => fake()->paragraph(),
            'is_spoiler' => false,
        ];
    }
}
