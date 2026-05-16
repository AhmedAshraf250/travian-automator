<?php

namespace App\Application\Accounts\Construction;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Throwable;

/**
 * Runs one construction automation cycle across all eligible villages of an account.
 */
class RunAccountAutomation
{
    /**
     * Create a new automation runner instance.
     */
    public function __construct(
        protected AccountSessionFactory $accountSessionFactory,
        protected TravianLoginAction $travianLoginAction,
        protected ExecuteVillageConstruction $executeVillageConstruction,
    ) {}

    /**
     * Run one automation cycle for the provided account.
     */
    public function handle(Account $account, ?int $targetVillageId = null): void
    {
        if (! $account->is_active || ! SystemSetting::automationEnabled()) {
            return;
        }

        $account = Account::query()
            ->with([
                'villages' => fn ($query) => $query
                    ->where('is_active', true)
                    ->when($targetVillageId !== null, fn ($query) => $query->whereKey($targetVillageId))
                    ->orderBy('id'),
                'villages.settings',
                'villages.runtimeState',
                'villages.buildings' => fn ($query) => $query->orderBy('slot_id'),
                'villages.buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
            ])
            ->findOrFail($account->id);

        if ($account->villages->isEmpty()) {
            return;
        }

        try {
            $session = $this->accountSessionFactory->for($account);
            $this->travianLoginAction->handle($account, $session);

            foreach ($account->villages as $village) {
                $this->executeVillageConstruction->handle($account, $village, $session);
            }

            $session->persist();
        } catch (Throwable $throwable) {
            ActivityLog::query()->create([
                'account_id' => $account->id,
                'activity_type' => ActivityType::Build,
                'status' => ActivityLogStatus::Failed,
                'message' => 'Account automation cycle failed: '.$throwable->getMessage(),
                'executed_at' => now(),
            ]);
        }
    }
}
