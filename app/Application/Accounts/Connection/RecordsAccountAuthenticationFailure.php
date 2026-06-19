<?php

namespace App\Application\Accounts\Connection;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use Throwable;

/**
 * Applies a short retry ladder for rejected Travian login attempts.
 */
class RecordsAccountAuthenticationFailure
{
    public function handle(Account $account, ActivityType $activityType, ?Village $village, Throwable $throwable): void
    {
        $message = mb_strimwidth($throwable->getMessage(), 0, 500, '...');
        $failureCount = max(0, (int) $account->connection_failure_count) + 1;

        if ($failureCount >= 3) {
            $account->forceFill([
                'status' => AccountStatus::Paused,
                'is_active' => false,
                'connection_failure_count' => $failureCount,
                'connection_retry_after' => null,
                'last_connection_error_at' => now(),
                'last_connection_error_message' => $message,
                'last_error_at' => now(),
                'last_error_message' => $message,
                'next_automation_at' => null,
            ])->save();

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'village_id' => $village?->id,
                'activity_type' => $activityType,
                'status' => ActivityLogStatus::Failed,
                'message' => $message.' Account paused after 3 rejected login attempts.',
                'executed_at' => now(),
            ]);

            return;
        }

        $retryAfter = now()->addMinutes(min(10, $failureCount));

        $account->forceFill([
            'status' => AccountStatus::Error,
            'connection_failure_count' => $failureCount,
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
            'message' => $message." Retry {$failureCount}/3 scheduled for {$retryAfter->diffForHumans()}.",
            'executed_at' => now(),
        ]);
    }
}
