<?php

namespace Database\Factories;

use App\Models\Forum\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumTopicFactory extends Factory
{
    protected $model = ForumTopic::class;

    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'title'     => fake()->sentence(),
            'content'   => fake()->paragraph(),
            'category'  => fake()->randomElement(['general', 'anime', 'manga', 'recommendations', 'spoilers']),
            'is_pinned' => false,
            'is_locked' => false,
        ];
    }
}
