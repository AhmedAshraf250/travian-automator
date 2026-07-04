<?php

namespace App\Application\Accounts\Connection;

use App\Models\Account;
use App\Models\AccountProxy;
use Carbon\CarbonInterface;

/**
 * Records proxy-level failures and switches an account to the next healthy proxy.
 */
class RotatesAccountProxy
{
    public function recordFailure(Account $account, string $message): bool
    {
        $this->refreshExpiredCooldowns($account);
        $account->loadMissing('activeProxy');

        $activeProxy = $account->activeProxy;

        if (! $activeProxy instanceof AccountProxy) {
            return false;
        }

        $failureCount = max(0, (int) $activeProxy->failure_count) + 1;
        $lifetimeFailureCount = max(0, (int) $activeProxy->lifetime_failure_count) + 1;
        $shouldCoolDown = $failureCount >= $this->failureThreshold();

        $activeProxy->forceFill([
            'failure_count' => $failureCount,
            'lifetime_failure_count' => $lifetimeFailureCount,
            'last_failed_at' => now(),
            'last_error_message' => $message,
            'status' => $shouldCoolDown ? AccountProxy::StatusCooldown : AccountProxy::StatusActive,
            'cooldown_until' => $shouldCoolDown ? now()->addMinutes($this->cooldownMinutes()) : $activeProxy->cooldown_until,
        ])->save();

        if (! $shouldCoolDown) {
            return false;
        }

        $nextProxy = $this->nextAvailableProxy($account, $activeProxy);

        if (! $nextProxy instanceof AccountProxy) {
            return false;
        }

        $this->applyProxy($account, $nextProxy);

        return true;
    }

    public function clear(Account $account): void
    {
        $this->refreshExpiredCooldowns($account);
        $account->loadMissing('activeProxy');

        $activeProxy = $account->activeProxy;

        if (! $activeProxy instanceof AccountProxy) {
            return;
        }

        $activeProxy->forceFill([
            'status' => AccountProxy::StatusActive,
            'failure_count' => 0,
            'last_failed_at' => null,
            'cooldown_until' => null,
            'last_error_message' => null,
        ])->save();
    }

    public function applyProxy(Account $account, AccountProxy $proxy): void
    {
        $proxy->forceFill([
            'status' => AccountProxy::StatusActive,
            'failure_count' => 0,
            'cooldown_until' => null,
            'last_error_message' => null,
        ])->save();

        $account->forceFill([
            'active_account_proxy_id' => $proxy->id,
            'proxy_scheme' => $proxy->scheme,
            'proxy_ip' => $proxy->host,
            'proxy_port' => $proxy->port,
            'proxy_username' => $proxy->username,
            'proxy_password' => $proxy->password,
            'session_cookies' => null,
            'session_transport_fingerprint' => null,
        ])->save();

        $account->setRelation('activeProxy', $proxy);
    }

    public function hasAvailableProxy(Account $account): bool
    {
        $this->refreshExpiredCooldowns($account);

        return $account->proxies()
            ->where('status', AccountProxy::StatusActive)
            ->exists();
    }

    public function nextProxyRetryAt(Account $account): ?CarbonInterface
    {
        $this->refreshExpiredCooldowns($account);

        /** @var AccountProxy|null $proxy */
        $proxy = $account->proxies()
            ->where('status', AccountProxy::StatusCooldown)
            ->whereNotNull('cooldown_until')
            ->where('cooldown_until', '>', now())
            ->orderBy('cooldown_until')
            ->first();

        return $proxy?->cooldown_until;
    }

    public function refreshExpiredCooldowns(?Account $account = null): int
    {
        $query = AccountProxy::query()
            ->where('status', AccountProxy::StatusCooldown)
            ->whereNotNull('cooldown_until')
            ->where('cooldown_until', '<=', now());

        if ($account instanceof Account) {
            $query->where('account_id', $account->id);
        }

        $updated = $query->update([
            'status' => AccountProxy::StatusActive,
            'failure_count' => 0,
            'cooldown_until' => null,
            'last_error_message' => null,
        ]);

        if ($account instanceof Account) {
            $account->unsetRelation('proxies');
            $account->unsetRelation('activeProxy');
        }

        return $updated;
    }

    public function failureThreshold(): int
    {
        return max(1, (int) config('travian.proxy_pool.failure_threshold', 5));
    }

    protected function cooldownMinutes(): int
    {
        return max(1, (int) config('travian.proxy_pool.cooldown_minutes', 10));
    }

    protected function nextAvailableProxy(Account $account, AccountProxy $failedProxy): ?AccountProxy
    {
        $this->refreshExpiredCooldowns($account);

        return $account->proxies()
            ->whereKeyNot($failedProxy->id)
            ->where('status', AccountProxy::StatusActive)
            ->orderBy('failure_count')
            ->orderBy('position')
            ->orderBy('id')
            ->first();
    }
}
