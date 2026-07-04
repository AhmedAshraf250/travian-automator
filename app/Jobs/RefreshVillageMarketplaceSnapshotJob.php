<?php

namespace App\Jobs;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageBuilding;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RefreshVillageMarketplaceSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(
        public int $accountId,
        public int $villageId,
    ) {
        $this->timeout = max(30, (int) config('travian.automation.job_timeout_seconds', 90));
    }

    public function handle(AccountSessionFactory $accountSessionFactory, TravianLoginAction $travianLoginAction): void
    {
        if (! SystemSetting::automationEnabled()) {
            return;
        }

        $account = Account::query()->findOrFail($this->accountId);
        $village = Village::query()
            ->with('buildings')
            ->where('account_id', $account->id)
            ->findOrFail($this->villageId);

        if (! $account->is_active || ! $village->is_active) {
            return;
        }

        $marketSlot = $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 17);

        if (! $marketSlot instanceof VillageBuilding) {
            return;
        }

        $session = $accountSessionFactory->for($account);
        $travianLoginAction->handle($account, $session);
        $this->openMarket($account, $village, $marketSlot, $session);
        $session->persist();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Transfer,
            'status' => ActivityLogStatus::Done,
            'message' => 'Marketplace merchant snapshot refreshed.',
            'executed_at' => now(),
        ]);
    }

    public function failed(?Throwable $throwable): void
    {
        ActivityLog::query()->create([
            'account_id' => $this->accountId,
            'village_id' => $this->villageId,
            'activity_type' => ActivityType::Transfer,
            'status' => ActivityLogStatus::Failed,
            'message' => 'Marketplace merchant snapshot refresh failed: '.($throwable?->getMessage() ?? 'unknown error'),
            'executed_at' => now(),
        ]);
    }

    protected function openMarket(Account $account, Village $village, VillageBuilding $marketSlot, AccountSession $session): void
    {
        $switchResponse = $session->get(
            $this->resolveVillageSwitchUri($village),
            $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)),
        );
        $villageCenterResponse = $session->get(
            (string) config('travian.paths.village_center', '/dorf2.php'),
            $this->documentRequestOptions($switchResponse->effectiveUri),
        );
        $marketUri = (string) config('travian.paths.build', '/build.php')
            .'?id='.(int) $marketSlot->slot_id.'&gid=17';
        $session->get($marketUri, $this->documentRequestOptions($villageCenterResponse->effectiveUri));
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentRequestOptions(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
        ];

        if ($referer !== null && $referer !== '') {
            $headers['Referer'] = $referer;
        }

        return ['headers' => $headers];
    }

    protected function resolveVillageSwitchUri(Village $village): string
    {
        $travianVillageId = trim((string) $village->travian_village_id);

        return (string) config('travian.paths.overview', '/dorf1.php')
            .($travianVillageId !== '' ? '?newdid='.$travianVillageId : '');
    }

    protected function absoluteUri(string $uri, Account $account): string
    {
        if (preg_match('/^https?:\/\//i', $uri) === 1) {
            return $uri;
        }

        return rtrim($account->server_url, '/').'/'.ltrim($uri, '/');
    }
}
