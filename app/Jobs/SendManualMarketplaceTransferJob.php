<?php

namespace App\Jobs;

use App\Application\Accounts\Trading\ExecuteManualMarketplaceTransfer;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Queues a user-requested marketplace shipment.
 */
class SendManualMarketplaceTransferJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     */
    public function __construct(
        public int $accountId,
        public int $sourceVillageId,
        public int $x,
        public int $y,
        public array $resources,
    ) {
        $this->timeout = max(30, (int) config('travian.automation.job_timeout_seconds', 90));
    }

    public function handle(ExecuteManualMarketplaceTransfer $executeManualMarketplaceTransfer): void
    {
        if (! SystemSetting::automationEnabled()) {
            return;
        }

        $account = Account::query()->findOrFail($this->accountId);
        $sourceVillage = Village::query()
            ->where('account_id', $account->id)
            ->findOrFail($this->sourceVillageId);

        $executeManualMarketplaceTransfer->handle($account, $sourceVillage, $this->x, $this->y, $this->resources);
    }

    public function failed(?Throwable $throwable): void
    {
        ActivityLog::query()->create([
            'account_id' => $this->accountId,
            'village_id' => $this->sourceVillageId,
            'activity_type' => ActivityType::Transfer,
            'status' => ActivityLogStatus::Failed,
            'payload' => [
                'destination' => ['x' => $this->x, 'y' => $this->y],
                'resources' => $this->resources,
            ],
            'message' => 'Manual marketplace transfer job failed: '.($throwable?->getMessage() ?? 'unknown error'),
            'executed_at' => now(),
        ]);
    }
}
