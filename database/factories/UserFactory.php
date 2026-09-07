<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'role' => UserRole::Client,
            'status' => UserStatus::Active,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function moderator(): static
    {
        return $this->state(fn () => ['role' => UserRole::Moderator]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Blocked]);
    }
}
