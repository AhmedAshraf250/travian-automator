<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_url' => 'https://ts7.x1.arabics.travian.com/',
            'username' => fake()->unique()->userName(),
            'password' => 'secret-password',
            'proxy_scheme' => 'http',
            'proxy_ip' => null,
            'proxy_port' => null,
            'proxy_username' => null,
            'proxy_password' => null,
            'user_agent' => fake()->userAgent(),
            'session_cookies' => null,
            'session_transport_fingerprint' => null,
            'managed_by_import' => false,
            'is_archived' => false,
            'import_position' => 0,
            'is_active' => true,
            'status' => AccountStatus::Paused,
            'last_sync_at' => null,
            'connection_failure_count' => 0,
            'connection_retry_after' => null,
            'last_login_at' => null,
            'last_error_at' => null,
            'last_error_message' => null,
            'last_connection_error_at' => null,
            'last_connection_error_message' => null,
        ];
    }
}
