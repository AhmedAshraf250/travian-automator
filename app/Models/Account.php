<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use JsonException;

/**
 * Stores the root identity and transport isolation settings for a Travian account.
 */
#[Fillable([
    'server_url',
    'username',
    'password',
    'proxy_ip',
    'proxy_port',
    'proxy_username',
    'proxy_password',
    'user_agent',
    'session_cookies',
    'session_transport_fingerprint',
    'managed_by_import',
    'is_archived',
    'is_active',
    'status',
    'last_sync_at',
    'last_login_at',
    'last_error_at',
    'last_error_message',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'proxy_password' => 'encrypted',
            'session_cookies' => 'encrypted:array',
            'managed_by_import' => 'boolean',
            'is_archived' => 'boolean',
            'is_active' => 'boolean',
            'status' => AccountStatus::class,
            'last_sync_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'last_error_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the settings record assigned to the account.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(AccountSetting::class);
    }

    /**
     * Get all villages linked to the account.
     */
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    /**
     * Get all activity logs linked to the account.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Build a stable fingerprint for the transport identity of this account.
     */
    public function currentTransportFingerprint(): string
    {
        try {
            $payload = json_encode([
                'server_url' => $this->server_url,
                'proxy_ip' => $this->proxy_ip,
                'proxy_port' => $this->proxy_port,
                'proxy_username' => $this->proxy_username,
                'proxy_password' => $this->proxy_password,
                'user_agent' => $this->user_agent,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $payload = implode('|', [
                (string) $this->server_url,
                (string) $this->proxy_ip,
                (string) $this->proxy_port,
                (string) $this->proxy_username,
                (string) $this->proxy_password,
                (string) $this->user_agent,
            ]);
        }

        return hash('sha256', $payload);
    }

    /**
     * Resolve the effective user agent applied to this account.
     */
    public function effectiveUserAgent(): ?string
    {
        $accountUserAgent = is_string($this->user_agent) ? trim($this->user_agent) : '';

        if ($accountUserAgent !== '') {
            return $accountUserAgent;
        }

        return SystemSetting::defaultUserAgent();
    }
}
