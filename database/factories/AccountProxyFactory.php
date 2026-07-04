<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountProxy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountProxy>
 */
class AccountProxyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'scheme' => 'http',
            'host' => fake()->ipv4(),
            'port' => fake()->numberBetween(8000, 9999),
            'username' => null,
            'password' => null,
            'status' => AccountProxy::StatusActive,
            'position' => 1,
            'failure_count' => 0,
            'lifetime_failure_count' => 0,
            'last_failed_at' => null,
            'cooldown_until' => null,
            'last_error_message' => null,
        ];
    }
}
