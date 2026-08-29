<?php

namespace App\Jobs;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Sync\Parsers\Dorf2OverviewParser;
use App\Application\Accounts\Sync\PersistVillageOverview;
use App\Application\Accounts\Troops\RefreshVillageTroopSnapshot;
use App\Models\Account;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class RefreshVillageTroopSnapshotJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public int $uniqueFor = 120;

    public function __construct(public int $accountId, public int $villageId) {}

    public function uniqueId(): string
    {
        return (string) $this->villageId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("travian-account:{$this->accountId}"))
                ->shared()
                ->releaseAfter(15)
                ->expireAfter(600),
        ];
    }

    public function handle(
        AccountSessionFactory $accountSessionFactory,
        TravianLoginAction $travianLoginAction,
        Dorf2OverviewParser $dorf2OverviewParser,
        PersistVillageOverview $persistVillageOverview,
        RefreshVillageTroopSnapshot $refreshVillageTroopSnapshot,
    ): void {
        $account = Account::query()->findOrFail($this->accountId);

        if (! SystemSetting::automationEnabled() || ! $account->is_active || $account->is_archived) {
            return;
        }

        $village = Village::query()
            ->with('runtimeState', 'buildings')
            ->where('account_id', $account->id)
            ->findOrFail($this->villageId);

        if (! $village->is_active) {
            return;
        }
        $session = $accountSessionFactory->for($account);
        $travianLoginAction->handle($account, $session);
        $session->get(
            (string) config('travian.paths.overview', '/dorf1.php')
            .'?newdid='.rawurlencode((string) $village->travian_village_id),
        );
        $dorf2Response = $session->get((string) config('travian.paths.village_center', '/dorf2.php'));
        $persistVillageOverview->handleDorf2Only($village, $dorf2OverviewParser->parse($dorf2Response->body));
        $village->unsetRelation('buildings');
        $village->load('runtimeState', 'buildings');
        $refreshVillageTroopSnapshot->handle($account, $village, $session);
        $session->persist();
    }
}
