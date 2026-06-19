<?php

namespace App\Application\Accounts\Sync;

use App\Application\Accounts\Connection\RecordsAccountAuthenticationFailure;
use App\Application\Accounts\Connection\RecordsAccountConnectionFailure;
use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Exceptions\AuthenticationFailedException;
use App\Application\Accounts\Session\Exceptions\ExternalAccountRequestsPaused;
use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedDorf2Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageSummary;
use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;
use App\Application\Accounts\Sync\Parsers\Dorf2OverviewParser;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Synchronizes account and village overview data from the live Travian dorf pages.
 */
class SyncAccountOverview
{
    /**
     * Create a new sync service instance.
     */
    public function __construct(
        protected AccountSessionFactory $accountSessionFactory,
        protected TravianLoginAction $travianLoginAction,
        protected Dorf1OverviewParser $dorf1OverviewParser,
        protected Dorf2OverviewParser $dorf2OverviewParser,
        protected PersistVillageOverview $persistVillageOverview,
        protected RecordsAccountAuthenticationFailure $recordsAccountAuthenticationFailure,
        protected RecordsAccountConnectionFailure $recordsAccountConnectionFailure,
    ) {}

    /**
     * Synchronize the given account overview.
     */
    public function handle(Account $account, ?Village $targetVillage = null, bool $useReloadAuto = false): void
    {
        $accountShouldRemainActive = (bool) $account->is_active;
        $finalAccountStatus = $accountShouldRemainActive ? AccountStatus::Active : AccountStatus::Paused;

        $account->forceFill([
            'status' => AccountStatus::Syncing,
        ])->save();

        try {
            $session = $this->accountSessionFactory->for($account);

            $this->travianLoginAction->handle($account, $session);

            $villageSnapshots = $targetVillage instanceof Village
                ? $this->collectSingleVillageSnapshot($session, $targetVillage, $useReloadAuto)
                : $this->collectAllVillageSnapshots($account, $session, $useReloadAuto);

            DB::transaction(function () use ($account, $villageSnapshots, $finalAccountStatus, $accountShouldRemainActive, $targetVillage): void {
                foreach ($villageSnapshots as $snapshot) {
                    $this->synchronizeVillage($account, $snapshot['summary'], $snapshot['dorf1'], $snapshot['dorf2']);
                }

                $account->forceFill([
                    'status' => $finalAccountStatus,
                    'is_active' => $accountShouldRemainActive,
                    'last_sync_at' => now(),
                    'last_error_at' => null,
                    'last_error_message' => null,
                ]);

                $this->recordsAccountConnectionFailure->clear($account);
                $account->save();

                ActivityLog::query()->create([
                    'account_id' => $account->id,
                    'village_id' => $targetVillage?->id,
                    'activity_type' => ActivityType::Sync,
                    'status' => ActivityLogStatus::Done,
                    'message' => $targetVillage instanceof Village
                        ? 'Village overview synced successfully from dorf1 and dorf2.'
                        : 'Account overview synced successfully from dorf1 and dorf2.',
                    'executed_at' => now(),
                ]);
            });

            $session->persist();
        } catch (ExternalAccountRequestsPaused $throwable) {
            $account->refresh();

            $account->forceFill([
                'status' => $account->is_active ? AccountStatus::Active : AccountStatus::Paused,
            ])->save();

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'village_id' => $targetVillage?->id,
                'activity_type' => ActivityType::Sync,
                'status' => ActivityLogStatus::Done,
                'message' => $throwable->getMessage(),
                'executed_at' => now(),
            ]);
        } catch (AuthenticationFailedException $throwable) {
            $this->recordsAccountAuthenticationFailure->handle($account, ActivityType::Sync, $targetVillage, $throwable);
        } catch (Throwable $throwable) {
            $message = $this->normalizeSyncErrorMessage($throwable);

            if ($this->recordsAccountConnectionFailure->shouldBackOff($throwable)) {
                throw $this->recordsAccountConnectionFailure->handle($account, ActivityType::Sync, $targetVillage, $throwable);
            }

            $account->forceFill([
                'status' => AccountStatus::Error,
                'last_error_at' => now(),
                'last_error_message' => $message,
            ])->save();

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'village_id' => $targetVillage?->id,
                'activity_type' => ActivityType::Sync,
                'status' => ActivityLogStatus::Failed,
                'message' => $message,
                'executed_at' => now(),
            ]);

            throw $throwable;
        }
    }

    /**
     * Collect a synchronized dorf1 + dorf2 snapshot for the whole account.
     *
     * @return array<string, array{
     *     summary: ParsedVillageSummary,
     *     dorf1: ParsedDorf1Overview,
     *     dorf2: ParsedDorf2Overview
     * }>
     */
    protected function collectAllVillageSnapshots(Account $account, AccountSession $session, bool $useReloadAuto): array
    {
        $dorf1Response = $session->get($this->dorf1Path($useReloadAuto));
        $this->dumpDorf1Response($account, $dorf1Response->body, $dorf1Response->effectiveUri);

        $initialDorf1Overview = $this->dorf1OverviewParser->parse($dorf1Response->body);
        $dorf2Response = $session->get((string) config('travian.paths.village_center', '/dorf2.php'));
        $initialDorf2Overview = $this->dorf2OverviewParser->parse($dorf2Response->body);

        return $this->collectVillageSnapshots($session, $initialDorf1Overview, $initialDorf2Overview, $useReloadAuto);
    }

    /**
     * Collect a synchronized dorf1 + dorf2 snapshot for one village only.
     *
     * @return array<string, array{
     *     summary: ParsedVillageSummary,
     *     dorf1: ParsedDorf1Overview,
     *     dorf2: ParsedDorf2Overview
     * }>
     */
    protected function collectSingleVillageSnapshot(AccountSession $session, Village $targetVillage, bool $useReloadAuto): array
    {
        $session->get(
            (string) config('travian.paths.overview', '/dorf1.php')
            .'?newdid='.rawurlencode((string) $targetVillage->travian_village_id),
        );

        $dorf1Response = $session->get($this->dorf1Path($useReloadAuto));
        $dorf1Overview = $this->dorf1OverviewParser->parse($dorf1Response->body);
        $dorf2Response = $session->get((string) config('travian.paths.village_center', '/dorf2.php'));
        $dorf2Overview = $this->dorf2OverviewParser->parse($dorf2Response->body);

        return [
            $dorf1Overview->activeVillage->travianVillageId => [
                'summary' => $dorf1Overview->activeVillage,
                'dorf1' => $dorf1Overview,
                'dorf2' => $dorf2Overview,
            ],
        ];
    }

    /**
     * Collect a synchronized dorf1 + dorf2 snapshot for every discovered village.
     *
     * @return array<string, array{
     *     summary: ParsedVillageSummary,
     *     dorf1: ParsedDorf1Overview,
     *     dorf2: ParsedDorf2Overview
     * }>
     */
    protected function collectVillageSnapshots(
        AccountSession $session,
        ParsedDorf1Overview $initialDorf1Overview,
        ParsedDorf2Overview $initialDorf2Overview,
        bool $useReloadAuto,
    ): array {
        $snapshots = [
            $initialDorf1Overview->activeVillage->travianVillageId => [
                'summary' => $initialDorf1Overview->activeVillage,
                'dorf1' => $initialDorf1Overview,
                'dorf2' => $initialDorf2Overview,
            ],
        ];

        foreach ($this->trackedVillages($initialDorf1Overview) as $villageSummary) {
            if ($villageSummary->travianVillageId === $initialDorf1Overview->activeVillage->travianVillageId) {
                continue;
            }

            $session->get($this->resolveVillageSwitchUri($villageSummary));

            $dorf1Response = $session->get($this->dorf1Path($useReloadAuto));
            $dorf1Overview = $this->dorf1OverviewParser->parse($dorf1Response->body);
            $dorf2Response = $session->get((string) config('travian.paths.village_center', '/dorf2.php'));
            $dorf2Overview = $this->dorf2OverviewParser->parse($dorf2Response->body);

            $snapshots[$dorf1Overview->activeVillage->travianVillageId] = [
                'summary' => $dorf1Overview->activeVillage,
                'dorf1' => $dorf1Overview,
                'dorf2' => $dorf2Overview,
            ];
        }

        return $snapshots;
    }

    /**
     * Build the unique list of villages that should be traversed during sync.
     *
     * @return list<ParsedVillageSummary>
     */
    protected function trackedVillages(ParsedDorf1Overview $overview): array
    {
        $villagesById = [
            $overview->activeVillage->travianVillageId => $overview->activeVillage,
        ];

        foreach ($overview->villages as $villageSummary) {
            $existingVillage = $villagesById[$villageSummary->travianVillageId] ?? null;

            if ($existingVillage === null) {
                $villagesById[$villageSummary->travianVillageId] = $villageSummary;

                continue;
            }

            if ($existingVillage->switchUri === null && $villageSummary->switchUri !== null) {
                $villagesById[$villageSummary->travianVillageId] = new ParsedVillageSummary(
                    travianVillageId: $existingVillage->travianVillageId,
                    name: $existingVillage->name,
                    x: $existingVillage->x,
                    y: $existingVillage->y,
                    population: $existingVillage->population,
                    isActive: $existingVillage->isActive,
                    switchUri: $villageSummary->switchUri,
                );
            }
        }

        return array_values($villagesById);
    }

    /**
     * Persist one village snapshot.
     */
    protected function synchronizeVillage(
        Account $account,
        ParsedVillageSummary $summary,
        ParsedDorf1Overview $dorf1Overview,
        ParsedDorf2Overview $dorf2Overview,
    ): void {
        $village = $account->villages()->firstOrNew([
            'travian_village_id' => $summary->travianVillageId,
        ]);

        $this->persistVillageOverview->handle($village, $summary, $dorf1Overview, $dorf2Overview);
    }

    /**
     * Resolve the relative URI that switches the current session to a target village.
     */
    protected function resolveVillageSwitchUri(ParsedVillageSummary $villageSummary): string
    {
        $switchUri = $villageSummary->switchUri;

        if ($switchUri === null || $switchUri === '') {
            $baseOverviewPath = (string) config('travian.paths.overview', '/dorf1.php');

            return $baseOverviewPath.'?newdid='.rawurlencode($villageSummary->travianVillageId);
        }

        if (str_starts_with($switchUri, '?')) {
            return (string) config('travian.paths.overview', '/dorf1.php').$switchUri;
        }

        return $switchUri;
    }

    protected function dorf1Path(bool $useReloadAuto): string
    {
        $path = (string) config('travian.paths.overview', '/dorf1.php');

        return $useReloadAuto ? $this->appendReloadAuto($path) : $path;
    }

    protected function appendReloadAuto(string $uri): string
    {
        if (preg_match('/([?&])reload=[^&]*/', $uri) === 1) {
            return preg_replace('/([?&])reload=[^&]*/', '$1reload=auto', $uri) ?? $uri;
        }

        return $uri.(str_contains($uri, '?') ? '&' : '?').'reload=auto';
    }

    /**
     * Normalize low-level transport errors into actionable sync messages.
     */
    protected function normalizeSyncErrorMessage(Throwable $throwable): string
    {
        $message = $throwable->getMessage();

        if ($throwable instanceof RequestException && str_contains($message, 'cURL error 60')) {
            return 'SSL verification failed. Configure TRAVIAN_HTTP_CA_BUNDLE in .env with a valid cacert.pem path, or fix the PHP/cURL CA store.';
        }

        if ($throwable instanceof RuntimeException && str_contains($message, 'TRAVIAN_HTTP_CA_BUNDLE points to a non-existent file:')) {
            return $message;
        }

        return $message;
    }

    /**
     * Persist the live dorf1 response for debugging when enabled.
     */
    protected function dumpDorf1Response(Account $account, string $html, string $effectiveUri): void {}
}
