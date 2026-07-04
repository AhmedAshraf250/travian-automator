<?php

namespace App\Application\Accounts\Construction;

use App\Application\Accounts\Construction\Parsers\MainBuildingDemolitionParser;
use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Models\Account;
use App\Models\Village;
use App\Models\VillageBuilding;
use Carbon\CarbonInterface;

/**
 * Opens the Main Building and persists the visible demolition state.
 */
class RefreshVillageDemolitionSnapshot
{
    public function __construct(
        protected AccountSessionFactory $accountSessionFactory,
        protected TravianLoginAction $travianLoginAction,
        protected MainBuildingDemolitionParser $parser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Account $account, Village $village): array
    {
        $village = $village->fresh(['buildings', 'runtimeState']);

        if (! $village instanceof Village || ! $account->is_active || ! $village->is_active) {
            return [];
        }

        $mainBuilding = $this->mainBuilding($village);

        if (! $mainBuilding instanceof VillageBuilding) {
            $snapshot = $this->persistSnapshot($village, [
                'main_building_level' => null,
                'available_buildings' => [],
                'active' => null,
                'error' => 'No synced Main Building found in this village.',
            ]);

            return $snapshot;
        }

        $session = $this->accountSessionFactory->for($account);
        $this->travianLoginAction->handle($account, $session);
        $mainBuildingResponse = $this->openMainBuilding($account, $village, $mainBuilding, $session);
        $snapshot = $this->parser->parse($mainBuildingResponse->body);

        $session->persist();

        return $this->persistSnapshot($village, $snapshot);
    }

    public function openMainBuilding(Account $account, Village $village, VillageBuilding $mainBuilding, AccountSession $session): SessionResponse
    {
        $switchResponse = $session->get(
            $this->resolveVillageSwitchUri($village),
            $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)),
        );
        $villageCenterResponse = $session->get(
            (string) config('travian.paths.village_center', '/dorf2.php'),
            $this->documentRequestOptions($switchResponse->effectiveUri),
        );
        $mainBuildingUri = $this->mainBuildingUri($mainBuilding);

        return $session->get($mainBuildingUri, $this->documentRequestOptions($villageCenterResponse->effectiveUri));
    }

    public function mainBuildingUri(VillageBuilding $mainBuilding): string
    {
        return (string) config('travian.paths.build', '/build.php')
            .'?id='.(int) $mainBuilding->slot_id.'&gid=15';
    }

    /**
     * @return array<string, mixed>
     */
    public function persistSnapshot(Village $village, array $snapshot): array
    {
        $recordedAt = now();
        $mainBuildingLevel = $snapshot['main_building_level'] ?? null;

        if ($mainBuildingLevel === null) {
            $mainBuildingLevel = $this->mainBuilding($village)?->current_level;
        }

        $payload = [
            'main_building_level' => $mainBuildingLevel !== null ? (int) $mainBuildingLevel : null,
            'available_buildings' => array_values($snapshot['available_buildings'] ?? []),
            'active' => $this->normalizeActiveDemolition($snapshot['active'] ?? null, $recordedAt),
            'error' => $snapshot['error'] ?? null,
            'recorded_at' => $recordedAt->toIso8601String(),
        ];

        $village->runtimeState()->updateOrCreate([], [
            'demolition_entries' => $payload,
            'server_reported_at' => $recordedAt,
        ]);

        $this->syncVisibleBuildingLevels($village, $payload['available_buildings']);

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $availableBuildings
     */
    protected function syncVisibleBuildingLevels(Village $village, array $availableBuildings): void
    {
        foreach ($availableBuildings as $building) {
            $slotId = (int) ($building['slot_id'] ?? 0);

            if ($slotId < 19 || $slotId > 40) {
                continue;
            }

            $village->buildings()
                ->where('slot_id', $slotId)
                ->update([
                    'building_type' => (string) ($building['name'] ?? 'Building'),
                    'current_level' => max(0, (int) ($building['level'] ?? 0)),
                ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function normalizeActiveDemolition(mixed $active, CarbonInterface $recordedAt): ?array
    {
        if (! is_array($active)) {
            return null;
        }

        return [
            'name' => (string) ($active['name'] ?? 'Building'),
            'target_level' => isset($active['target_level']) ? (int) $active['target_level'] : null,
            'remaining_seconds' => isset($active['remaining_seconds']) ? max(0, (int) $active['remaining_seconds']) : null,
            'remaining_label' => $active['remaining_label'] ?? null,
            'finish_label' => $active['finish_label'] ?? null,
            'cancel_uri' => $active['cancel_uri'] ?? null,
            'recorded_at' => $recordedAt->toIso8601String(),
        ];
    }

    protected function mainBuilding(Village $village): ?VillageBuilding
    {
        return $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 15);
    }

    /**
     * @return array<string, mixed>
     */
    public function documentRequestOptions(?string $referer = null): array
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
