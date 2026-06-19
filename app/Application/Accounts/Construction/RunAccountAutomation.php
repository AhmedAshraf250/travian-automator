<?php

namespace App\Application\Accounts\Construction;

use App\Application\Accounts\Celebrations\ExecuteVillageCelebration;
use App\Application\Accounts\Connection\RecordsAccountAuthenticationFailure;
use App\Application\Accounts\Connection\RecordsAccountConnectionFailure;
use App\Application\Accounts\Hero\ExecuteHeroAutomation;
use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Exceptions\AuthenticationFailedException;
use App\Application\Accounts\Session\Exceptions\ExternalAccountRequestsPaused;
use App\Application\Accounts\Trading\ExecuteVillageResourceTransfer;
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
        protected ExecuteVillageResourceTransfer $executeVillageResourceTransfer,
        protected ExecuteVillageCelebration $executeVillageCelebration,
        protected ExecuteHeroAutomation $executeHeroAutomation,
        protected RecordsAccountAuthenticationFailure $recordsAccountAuthenticationFailure,
        protected RecordsAccountConnectionFailure $recordsAccountConnectionFailure,
    ) {}

    /**
     * Run one automation cycle for the provided account.
     */
    public function handle(Account $account, ?int $targetVillageId = null): void
    {
        if (! $account->is_active || $account->isWaitingForConnectionRetry() || ! SystemSetting::automationEnabled()) {
            return;
        }

        $account = Account::query()
            ->with([
                'villages' => fn ($query) => $query
                    ->where('is_active', true)
                    ->when($targetVillageId !== null, fn ($query) => $query->whereKey($targetVillageId))
                    ->orderBy('id'),
                'villages.settings',
                'villages.resourceState',
                'villages.runtimeState',
                'villages.buildings' => fn ($query) => $query->orderBy('slot_id'),
                'villages.buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
                'settings',
                'heroState',
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
                $this->executeVillageResourceTransfer->supportNegativeCrop($account, $village, $session);
                $this->executeVillageCelebration->handle($account, $village, $session);
            }

            $this->executeHeroAutomation->handle($account, $session);

            $this->recordsAccountConnectionFailure->clear($account);
            $session->persist();
        } catch (ExternalAccountRequestsPaused $throwable) {
            ActivityLog::query()->create([
                'account_id' => $account->id,
                'activity_type' => ActivityType::Build,
                'status' => ActivityLogStatus::Done,
                'message' => $throwable->getMessage(),
                'executed_at' => now(),
            ]);
        } catch (AuthenticationFailedException $throwable) {
            $this->recordsAccountAuthenticationFailure->handle($account, ActivityType::Build, null, $throwable);
        } catch (Throwable $throwable) {
            if ($this->recordsAccountConnectionFailure->shouldBackOff($throwable)) {
                throw $this->recordsAccountConnectionFailure->handle($account, ActivityType::Build, null, $throwable);
            }

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
