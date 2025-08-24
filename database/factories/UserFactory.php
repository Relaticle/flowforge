<?php

namespace Relaticle\Flowforge\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Relaticle\Flowforge\Tests\Fixtures\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Default password for testing
            'role' => $this->faker->randomElement(['admin', 'manager', 'user']),
            'team' => $this->faker->randomElement([
                'Development',
                'Design',
                'Product',
                'Marketing',
                'Operations',
                'QA',
            ]),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'manager',
        ]);
    }

    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    public function developer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
            'team' => 'Development',
        ]);
    }

    public function designer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
            'team' => 'Design',
        ]);
    }

    public function withTeam(string $team): static
    {
        return $this->state(fn (array $attributes) => [
            'team' => $team,
        ]);
    }
}
