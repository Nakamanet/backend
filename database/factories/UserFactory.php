<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username'      => fake()->unique()->userName(),
            'email'         => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make('password'),
            'birthdate'     => fake()->date(),
            'localisation'  => fake()->city(),
            'bio'           => fake()->sentence(),
            'avatar_url'    => null,
            'banner_url'    => null,
            'role'          => 'user',
            'is_deleted'    => false,
            'is_admin'      => false,
            'is_moderator'  => false,
        ];
    }
}
