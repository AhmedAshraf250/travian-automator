<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
}
