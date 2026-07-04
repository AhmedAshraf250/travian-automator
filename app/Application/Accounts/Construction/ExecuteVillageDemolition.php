<?php

namespace App\Application\Accounts\Construction;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use App\Models\VillageBuilding;

/**
 * Starts or cancels a Main Building demolition action.
 */
class ExecuteVillageDemolition
{
    private const string DEMOLISH_ENDPOINT = '/api/v1/building/demolish';

    public function __construct(
        protected AccountSessionFactory $accountSessionFactory,
        protected TravianLoginAction $travianLoginAction,
        protected RefreshVillageDemolitionSnapshot $refreshVillageDemolitionSnapshot,
    ) {}

    public function demolish(Account $account, Village $village, int $slotId): void
    {
        $village = $village->fresh(['buildings', 'runtimeState']);

        if (! $village instanceof Village || ! $account->is_active || ! $village->is_active) {
            return;
        }

        $mainBuilding = $this->mainBuilding($village);

        if (! $mainBuilding instanceof VillageBuilding || (int) $mainBuilding->current_level < 10) {
            $this->log($account, $village, ActivityLogStatus::Failed, 'Building demolition rejected: Main Building level 10 is required.', [
                'slot_id' => $slotId,
            ]);

            return;
        }

        $targetBuilding = $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->slot_id === $slotId);

        if (! $targetBuilding instanceof VillageBuilding || (int) $targetBuilding->current_level < 1) {
            $this->log($account, $village, ActivityLogStatus::Failed, 'Building demolition rejected: selected slot has no synced building level.', [
                'slot_id' => $slotId,
            ]);

            return;
        }

        $session = $this->accountSessionFactory->for($account);
        $this->travianLoginAction->handle($account, $session);
        $mainBuildingResponse = $this->refreshVillageDemolitionSnapshot->openMainBuilding($account, $village, $mainBuilding, $session);
        $response = $session->postJson(self::DEMOLISH_ENDPOINT, [
            'villageId' => (int) $village->travian_village_id,
            'slotId' => $slotId,
            'action' => 'demolishBuilding',
        ], $this->xhrRequestOptions($mainBuildingResponse->effectiveUri));

        $snapshot = $this->refreshVillageDemolitionSnapshot->handle($account, $village);
        $session->persist();

        if (! $response->successful() && ! $this->snapshotConfirmsDemolition($snapshot, $targetBuilding)) {
            $this->log($account, $village, ActivityLogStatus::Failed, 'Building demolition was rejected by Travian.', [
                'slot_id' => $slotId,
                'status_code' => $response->statusCode,
                'body' => mb_substr($response->body, 0, 500),
                'snapshot_active' => $snapshot['active'] ?? null,
            ]);

            return;
        }

        $this->log($account, $village, ActivityLogStatus::Done, 'Building demolition started.', [
            'slot_id' => $slotId,
            'building_name' => $targetBuilding->building_type,
            'current_level' => (int) $targetBuilding->current_level,
            'target_level' => max(0, (int) $targetBuilding->current_level - 1),
            'status_code' => $response->statusCode,
        ]);
    }

    public function cancel(Account $account, Village $village, string $cancelUri): void
    {
        $village = $village->fresh(['buildings', 'runtimeState']);

        if (! $village instanceof Village || ! $account->is_active || ! $village->is_active) {
            return;
        }

        if (! $this->isAllowedCancelUri($cancelUri)) {
            $this->log($account, $village, ActivityLogStatus::Failed, 'Building demolition cancel rejected: invalid cancel link.', [
                'cancel_uri' => $cancelUri,
            ]);

            return;
        }

        $mainBuilding = $this->mainBuilding($village);

        if (! $mainBuilding instanceof VillageBuilding) {
            return;
        }

        $session = $this->accountSessionFactory->for($account);
        $this->travianLoginAction->handle($account, $session);
        $mainBuildingResponse = $this->refreshVillageDemolitionSnapshot->openMainBuilding($account, $village, $mainBuilding, $session);
        $response = $session->get($cancelUri, $this->refreshVillageDemolitionSnapshot->documentRequestOptions($mainBuildingResponse->effectiveUri));
        $snapshot = $this->refreshVillageDemolitionSnapshot->handle($account, $village);
        $session->persist();

        if (! $response->successful() && $this->snapshotStillHasDemolition($snapshot)) {
            $this->log($account, $village, ActivityLogStatus::Failed, 'Building demolition cancel was rejected by Travian.', [
                'cancel_uri' => $cancelUri,
                'status_code' => $response->statusCode,
                'body' => mb_substr($response->body, 0, 500),
                'snapshot_active' => $snapshot['active'] ?? null,
            ]);

            return;
        }

        $this->log($account, $village, ActivityLogStatus::Done, 'Building demolition cancelled.', [
            'cancel_uri' => $cancelUri,
            'status_code' => $response->statusCode,
        ]);
    }

    protected function mainBuilding(Village $village): ?VillageBuilding
    {
        return $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 15);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected function snapshotConfirmsDemolition(array $snapshot, VillageBuilding $targetBuilding): bool
    {
        $active = $snapshot['active'] ?? null;

        if (! is_array($active)) {
            return false;
        }

        $targetLevel = $active['target_level'] ?? null;

        if ($targetLevel !== null && (int) $targetLevel === max(0, (int) $targetBuilding->current_level - 1)) {
            return true;
        }

        $activeName = trim((string) ($active['name'] ?? ''));
        $buildingName = trim((string) $targetBuilding->building_type);

        return $activeName !== '' && $buildingName !== '' && $activeName === $buildingName;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected function snapshotStillHasDemolition(array $snapshot): bool
    {
        return is_array($snapshot['active'] ?? null);
    }

    protected function isAllowedCancelUri(string $cancelUri): bool
    {
        $path = parse_url($cancelUri, PHP_URL_PATH) ?: $cancelUri;
        $query = parse_url($cancelUri, PHP_URL_QUERY) ?: '';

        if (str_ends_with((string) $path, '/build.php') || $path === 'build.php') {
            return str_contains($query, 'gid=15') && str_contains($query, 'del=');
        }

        return str_contains($cancelUri, 'build.php?gid=15') && str_contains($cancelUri, 'del=');
    }

    /**
     * @return array<string, mixed>
     */
    protected function xhrRequestOptions(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json; charset=UTF-8',
            'Pragma' => 'no-cache',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        if ($referer !== null && $referer !== '') {
            $headers['Referer'] = $referer;
        }

        return ['headers' => $headers];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function log(Account $account, Village $village, ActivityLogStatus $status, string $message, array $payload = []): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Manual,
            'status' => $status,
            'payload' => $payload,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
