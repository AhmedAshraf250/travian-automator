<?php

namespace App\Models;

use Database\Factories\AccountProxyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores one candidate proxy transport for a Travian account.
 */
#[Fillable([
    'account_id',
    'scheme',
    'host',
    'port',
    'username',
    'password',
    'status',
    'position',
    'failure_count',
    'lifetime_failure_count',
    'last_failed_at',
    'cooldown_until',
    'last_error_message',
])]
class AccountProxy extends Model
{
    /** @use HasFactory<AccountProxyFactory> */
    use HasFactory;

    public const StatusActive = 'active';

    public const StatusCooldown = 'cooldown';

    public const StatusDisabled = 'disabled';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'port' => 'integer',
            'position' => 'integer',
            'failure_count' => 'integer',
            'lifetime_failure_count' => 'integer',
            'last_failed_at' => 'immutable_datetime',
            'cooldown_until' => 'immutable_datetime',
        ];
    }

    /**
     * Get the account that owns this proxy option.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Determine whether this proxy can be selected right now.
     */
    public function isAvailable(): bool
    {
        if ($this->status === self::StatusDisabled) {
            return false;
        }

        return $this->cooldown_until === null || $this->cooldown_until->isPast();
    }

    /**
     * Return the scheme that should be used by cURL.
     */
    public function curlScheme(): string
    {
        return match ($this->scheme) {
            'socks5' => 'socks5h',
            'socks4' => 'socks4a',
            default => $this->scheme,
        };
    }

    /**
     * Return a compact display label for the proxy endpoint.
     */
    public function endpointLabel(): string
    {
        return "{$this->scheme}://{$this->host}:{$this->port}";
    }
}
