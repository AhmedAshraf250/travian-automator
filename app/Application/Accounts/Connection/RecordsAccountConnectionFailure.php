<?php

namespace App\Application\Accounts\Connection;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Throwable;

/**
 * Applies per-account cooldown after transient network/proxy failures.
 */
class RecordsAccountConnectionFailure
{
    public function __construct(protected RotatesAccountProxy $rotatesAccountProxy) {}

    public function handle(Account $account, ActivityType $activityType, ?Village $village, Throwable $throwable): AccountConnectionBackoffStarted
    {
        $message = $this->normalizedMessage($throwable);
        $failureCount = max(0, (int) $account->connection_failure_count) + 1;
        $switchedProxy = $this->rotatesAccountProxy->recordFailure($account, $message);
        $nextProxyRetryAt = $this->rotatesAccountProxy->nextProxyRetryAt($account);
        $hasAvailableProxy = $this->rotatesAccountProxy->hasAvailableProxy($account);

        if ($switchedProxy) {
            $retryAfter = now()->addMinutes(max(1, (int) config('travian.proxy_pool.switch_retry_minutes', 1)));
        } elseif (! $hasAvailableProxy && $nextProxyRetryAt !== null) {
            $retryAfter = $nextProxyRetryAt->addSeconds(max(0, (int) config('travian.proxy_pool.all_cooling_retry_grace_seconds', 10)));
        } else {
            $retryAfter = now()->addMinutes(min(10, $failureCount));
        }

        $retryDelaySeconds = max(1, (int) now()->diffInSeconds($retryAfter, false));

        $account->forceFill([
            'status' => AccountStatus::ConnectionIssue,
            'connection_failure_count' => $switchedProxy ? 0 : $failureCount,
            'connection_retry_after' => $retryAfter,
            'last_connection_error_at' => now(),
            'last_connection_error_message' => $message,
            'last_error_at' => now(),
            'last_error_message' => $message,
            'next_automation_at' => $retryAfter,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village?->id,
            'activity_type' => $activityType,
            'status' => ActivityLogStatus::Failed,
            'message' => 'Connection failed. '
                .($switchedProxy ? 'Switched to the next proxy. ' : '')
                .(! $hasAvailableProxy && $nextProxyRetryAt !== null ? 'All proxies are cooling. ' : '')
                .'Retry scheduled for '
                .$this->formatRetryDelay($retryDelaySeconds)
                ." from now: {$message}",
            'executed_at' => now(),
        ]);

        return new AccountConnectionBackoffStarted("Connection retry scheduled for {$retryAfter->diffForHumans()}.");
    }

    public function clear(Account $account): void
    {
        $this->rotatesAccountProxy->clear($account);

        $account->forceFill([
            'connection_failure_count' => 0,
            'connection_retry_after' => null,
            'last_connection_error_at' => null,
            'last_connection_error_message' => null,
        ]);
    }

    public function shouldBackOff(Throwable $throwable): bool
    {
        if ($throwable instanceof ConnectException) {
            return true;
        }

        $message = $throwable->getMessage();

        if ($throwable instanceof RequestException || str_contains($message, 'cURL error')) {
            return preg_match('/cURL error (5|6|7|28|35|52|56)\b/', $message) === 1;
        }

        return false;
    }

    protected function normalizedMessage(Throwable $throwable): string
    {
        $message = $throwable->getMessage();

        if (str_contains($message, 'cURL error 7') && str_contains($message, 'CONNECT tunnel failed')) {
            $message = 'Proxy tunnel failed before reaching Travian. '.$message;
        }

        if (str_contains($message, 'cURL error 28')) {
            $message = 'Proxy or Travian connection timed out. '.$message;
        }

        return mb_strimwidth($message, 0, 500, '...');
    }

    protected function formatRetryDelay(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' '.str('second')->plural($seconds);
        }

        $minutes = (int) ceil($seconds / 60);

        return $minutes.' '.str('minute')->plural($minutes);
    }
}
