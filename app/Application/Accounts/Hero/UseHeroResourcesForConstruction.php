<?php

namespace App\Application\Accounts\Hero;

use App\Application\Accounts\Construction\Data\BuildPageAnalysis;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use App\Models\VillageResourceState;
use App\Models\VillageSetting;
use Throwable;

/**
 * Moves stacked hero resource items into the active village to unblock construction.
 */
class UseHeroResourcesForConstruction
{
    private const int NEGATIVE_CROP_SAFETY_MINUTES = 15;

    private const string GRAPHQL_ENDPOINT = '/api/v1/graphql';

    private const string USE_ITEM_ENDPOINT = '/api/v1/hero/v2/inventory/use-item';

    private const string HERO_RESOURCES_QUERY = '{ownPlayer{hero{inventory{id amount placeId name typeId slot}}village{id name resources{lumberStock clayStock ironStock cropStock maxStorageCapacity maxCropStorageCapacity}}}}';

    private const array RESOURCE_ITEM_TYPES = [
        'wood' => 145,
        'clay' => 146,
        'iron' => 147,
        'crop' => 148,
    ];

    /**
     * Read the resource stacks currently held in the Hero inventory.
     *
     * @return array{resources: array{wood: int, clay: int, iron: int, crop: int}, travian_village_id: int|string|null, effective_uri: string, status_code: int}|null
     */
    public function readAvailableResources(AccountSession $session, string $referer): ?array
    {
        $inventorySnapshot = $this->fetchHeroInventory($session, $referer);

        if ($inventorySnapshot === null) {
            return null;
        }

        return [
            'resources' => $this->availableHeroResources($this->heroResourceItems($inventorySnapshot['items'])),
            'travian_village_id' => $inventorySnapshot['travian_village_id'],
            'effective_uri' => $inventorySnapshot['effective_uri'],
            'status_code' => $inventorySnapshot['status_code'],
        ];
    }

    /**
     * Try to cover one construction resource shortage from the hero inventory.
     *
     * @param  array<string, mixed>  $constructionPayload
     */
    public function handle(
        Account $account,
        Village $village,
        AccountSession $session,
        array $constructionPayload,
        BuildPageAnalysis $analysis,
    ): bool {
        if (! $analysis->isResourceShortage()) {
            return false;
        }

        $village = $village->fresh(['resourceState', 'settings']);

        if (! $village instanceof Village || ! $village->resourceState instanceof VillageResourceState) {
            return false;
        }

        if ($village->settings instanceof VillageSetting && ! (bool) $village->settings->hero_resources_enabled) {
            return false;
        }

        $referer = $this->absoluteUri(
            (string) ($constructionPayload['build_effective_uri'] ?? $constructionPayload['build_page_uri'] ?? config('travian.paths.overview', '/dorf1.php')),
            $account,
        );
        $inventorySnapshot = $this->fetchHeroInventory($session, $referer);

        if ($inventorySnapshot === null) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Failed, 'Hero resource inventory could not be read before construction.', [
                'construction' => $constructionPayload,
            ]);

            return false;
        }

        $liveResources = $inventorySnapshot['village_resources'];

        if (! $this->storageCanHoldRequiredResources($analysis->requiredResources, $liveResources)) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Pending, 'Hero resources skipped because village storage is too small for this construction.', [
                'construction' => $constructionPayload,
                'required_resources' => $analysis->requiredResources,
                'village_resources' => $liveResources,
            ]);

            return false;
        }

        $shortages = $this->resourceShortages($village, $analysis->requiredResources, $liveResources);

        if ($this->resourceListIsEmpty($shortages)) {
            return true;
        }

        $resourceItems = $this->heroResourceItems($inventorySnapshot['items']);
        $uncoveredResources = $this->uncoveredResources($shortages, $resourceItems);

        if ($uncoveredResources !== []) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Pending, 'Hero resources could not fully cover construction shortage.', [
                'construction' => $constructionPayload,
                'needed_resources' => $shortages,
                'uncovered_resources' => $uncoveredResources,
                'available_hero_resources' => $this->availableHeroResources($resourceItems),
            ]);

            return false;
        }

        $travianVillageId = $this->travianVillageId($village, $inventorySnapshot['travian_village_id']);

        if ($travianVillageId === null) {
            return false;
        }

        $preparedUses = $this->previewHeroResourceUses($session, $referer, $shortages, $resourceItems, $travianVillageId);

        if ($preparedUses === null) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Failed, 'Hero resource use preview was rejected by Travian.', [
                'construction' => $constructionPayload,
                'needed_resources' => $shortages,
            ]);

            return false;
        }

        [$usedResources, $allConfirmed] = $this->confirmHeroResourceUses($session, $referer, $preparedUses);

        if (! $this->resourceListIsEmpty($usedResources)) {
            $this->addLocalVillageResources($village, $usedResources);
        }

        if (! $allConfirmed) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Failed, 'Hero resource use confirmation was rejected by Travian.', [
                'construction' => $constructionPayload,
                'needed_resources' => $shortages,
                'used_resources' => $usedResources,
            ]);

            return false;
        }

        $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Done, 'Hero resources moved to village for construction.', [
            'construction' => $constructionPayload,
            'resources' => $usedResources,
            'effective_uri' => $inventorySnapshot['effective_uri'],
            'status_code' => $inventorySnapshot['status_code'],
            'item_ids' => array_map(
                static fn (array $preparedUse): int => (int) $preparedUse['payload']['itemId'],
                $preparedUses,
            ),
        ]);

        return true;
    }

    /**
     * @return array{items: list<array<string, mixed>>, village_resources: array<string, mixed>, travian_village_id: int|string|null, effective_uri: string, status_code: int}|null
     */
    protected function fetchHeroInventory(AccountSession $session, string $referer): ?array
    {
        try {
            $response = $session->postJson(self::GRAPHQL_ENDPOINT, [
                'query' => self::HERO_RESOURCES_QUERY,
            ], $this->xhrRequestOptions($referer));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = json_decode($response->body, true);

        if (! is_array($payload)) {
            return null;
        }

        $inventory = $payload['data']['ownPlayer']['hero']['inventory'] ?? null;
        $village = $payload['data']['ownPlayer']['village'] ?? null;

        if (! is_array($inventory) || ! is_array($village)) {
            return null;
        }

        return [
            'items' => array_values(array_filter($inventory, 'is_array')),
            'village_resources' => is_array($village['resources'] ?? null) ? $village['resources'] : [],
            'travian_village_id' => $village['id'] ?? null,
            'effective_uri' => $response->effectiveUri,
            'status_code' => $response->statusCode,
        ];
    }

    /**
     * @param  array<string, mixed>  $requiredResources
     * @param  array<string, mixed>  $liveResources
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function resourceShortages(Village $village, array $requiredResources, array $liveResources): array
    {
        $resourceState = $village->resourceState;
        $shortages = $this->emptyResources();
        $currentAmounts = $this->emptyResources();

        foreach ($this->resourceKeys() as $resourceKey) {
            $currentAmount = $this->liveResourceAmount($liveResources, $resourceKey);

            if ($currentAmount === null && $resourceState instanceof VillageResourceState) {
                $currentAmount = (int) $resourceState->{$resourceKey};
            }

            $currentAmounts[$resourceKey] = max(0, (int) $currentAmount);
            $shortages[$resourceKey] = max(0, (int) ($requiredResources[$resourceKey] ?? 0) - (int) $currentAmount);
        }

        if ($resourceState instanceof VillageResourceState && (int) $resourceState->crop_production < 0) {
            $safetyCrop = (int) ceil(abs((int) $resourceState->crop_production) * self::NEGATIVE_CROP_SAFETY_MINUTES / 60);
            $shortages['crop'] = max($shortages['crop'], max(0, $safetyCrop - $currentAmounts['crop']));
        }

        return $shortages;
    }

    /**
     * @param  array<string, mixed>  $requiredResources
     * @param  array<string, mixed>  $liveResources
     */
    protected function storageCanHoldRequiredResources(array $requiredResources, array $liveResources): bool
    {
        foreach ($this->resourceKeys() as $resourceKey) {
            $capacity = $this->liveStorageCapacity($liveResources, $resourceKey);

            if ($capacity !== null && $capacity > 0 && (int) ($requiredResources[$resourceKey] ?? 0) > $capacity) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, array{id: int, amount: int}>
     */
    protected function heroResourceItems(array $items): array
    {
        $resourceItems = [];

        foreach ($items as $item) {
            $typeId = (int) ($item['typeId'] ?? 0);
            $resourceKey = array_search($typeId, self::RESOURCE_ITEM_TYPES, true);

            if (! is_string($resourceKey)) {
                continue;
            }

            $itemId = (int) ($item['id'] ?? 0);
            $amount = max(0, (int) ($item['amount'] ?? 0));

            if ($itemId <= 0 || $amount <= 0) {
                continue;
            }

            if (isset($resourceItems[$resourceKey]) && (int) $resourceItems[$resourceKey]['amount'] >= $amount) {
                continue;
            }

            $resourceItems[$resourceKey] = [
                'id' => $itemId,
                'amount' => $amount,
            ];
        }

        return $resourceItems;
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $shortages
     * @param  array<string, array{id: int, amount: int}>  $resourceItems
     * @return array{wood?: int, clay?: int, iron?: int, crop?: int}
     */
    protected function uncoveredResources(array $shortages, array $resourceItems): array
    {
        $uncovered = [];

        foreach ($this->resourceKeys() as $resourceKey) {
            $neededAmount = (int) $shortages[$resourceKey];
            $availableAmount = (int) ($resourceItems[$resourceKey]['amount'] ?? 0);

            if ($neededAmount > $availableAmount) {
                $uncovered[$resourceKey] = $neededAmount - $availableAmount;
            }
        }

        return $uncovered;
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $shortages
     * @param  array<string, array{id: int, amount: int}>  $resourceItems
     * @return array<string, array{payload: array{action: string, itemId: int, amount: int, villageId: int}, nonce: string}>|null
     */
    protected function previewHeroResourceUses(
        AccountSession $session,
        string $referer,
        array $shortages,
        array $resourceItems,
        int $travianVillageId,
    ): ?array {
        $preparedUses = [];

        foreach ($this->resourceKeys() as $resourceKey) {
            $amount = (int) $shortages[$resourceKey];

            if ($amount <= 0) {
                continue;
            }

            $item = $resourceItems[$resourceKey] ?? null;

            if (! is_array($item)) {
                return null;
            }

            $payload = [
                'action' => 'inventory',
                'itemId' => (int) $item['id'],
                'amount' => $amount,
                'villageId' => $travianVillageId,
            ];
            $previewResponse = $session->putJson(self::USE_ITEM_ENDPOINT, $payload, $this->xhrRequestOptions($referer));
            $nonce = $this->headerValue($previewResponse, 'x-nonce');

            if (! $previewResponse->successful() || $nonce === null) {
                return null;
            }

            $preparedUses[$resourceKey] = [
                'payload' => $payload,
                'nonce' => $nonce,
            ];
        }

        return $preparedUses;
    }

    /**
     * @param  array<string, array{payload: array{action: string, itemId: int, amount: int, villageId: int}, nonce: string}>  $preparedUses
     * @return array{array{wood: int, clay: int, iron: int, crop: int}, bool}
     */
    protected function confirmHeroResourceUses(AccountSession $session, string $referer, array $preparedUses): array
    {
        $usedResources = $this->emptyResources();

        foreach ($preparedUses as $resourceKey => $preparedUse) {
            $confirmResponse = $session->postJson(
                self::USE_ITEM_ENDPOINT,
                $preparedUse['payload'],
                $this->xhrRequestOptions($referer, ['x-nonce' => $preparedUse['nonce']]),
            );

            if (! $confirmResponse->successful()) {
                return [$usedResources, false];
            }

            if (in_array($resourceKey, $this->resourceKeys(), true)) {
                $usedResources[$resourceKey] = (int) $preparedUse['payload']['amount'];
            }
        }

        return [$usedResources, true];
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $usedResources
     */
    protected function addLocalVillageResources(Village $village, array $usedResources): void
    {
        $resourceState = $village->resourceState;

        if (! $resourceState instanceof VillageResourceState) {
            return;
        }

        $resourceState->forceFill([
            'wood' => $this->addResourceWithinCapacity((int) $resourceState->wood, $usedResources['wood'], (int) $resourceState->warehouse_capacity),
            'clay' => $this->addResourceWithinCapacity((int) $resourceState->clay, $usedResources['clay'], (int) $resourceState->warehouse_capacity),
            'iron' => $this->addResourceWithinCapacity((int) $resourceState->iron, $usedResources['iron'], (int) $resourceState->warehouse_capacity),
            'crop' => $this->addResourceWithinCapacity((int) $resourceState->crop, $usedResources['crop'], (int) $resourceState->granary_capacity),
            'server_reported_at' => now(),
        ])->save();
    }

    protected function addResourceWithinCapacity(int $currentAmount, int $addedAmount, int $capacity): int
    {
        $newAmount = max(0, $currentAmount + $addedAmount);

        return $capacity > 0 ? min($capacity, $newAmount) : $newAmount;
    }

    /**
     * @param  array<string, array{id: int, amount: int}>  $resourceItems
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function availableHeroResources(array $resourceItems): array
    {
        $availableResources = $this->emptyResources();

        foreach ($this->resourceKeys() as $resourceKey) {
            $availableResources[$resourceKey] = (int) ($resourceItems[$resourceKey]['amount'] ?? 0);
        }

        return $availableResources;
    }

    /**
     * @param  array<string, mixed>  $liveResources
     */
    protected function liveResourceAmount(array $liveResources, string $resourceKey): ?int
    {
        $stockKey = match ($resourceKey) {
            'wood' => 'lumberStock',
            'clay' => 'clayStock',
            'iron' => 'ironStock',
            'crop' => 'cropStock',
            default => null,
        };

        return $stockKey !== null && isset($liveResources[$stockKey])
            ? (int) $liveResources[$stockKey]
            : null;
    }

    /**
     * @param  array<string, mixed>  $liveResources
     */
    protected function liveStorageCapacity(array $liveResources, string $resourceKey): ?int
    {
        $capacityKey = $resourceKey === 'crop'
            ? 'maxCropStorageCapacity'
            : 'maxStorageCapacity';

        return isset($liveResources[$capacityKey]) ? (int) $liveResources[$capacityKey] : null;
    }

    protected function travianVillageId(Village $village, mixed $liveVillageId): ?int
    {
        $travianVillageId = is_scalar($liveVillageId)
            ? (int) $liveVillageId
            : (int) $village->travian_village_id;

        return $travianVillageId > 0 ? $travianVillageId : null;
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $resources
     */
    protected function resourceListIsEmpty(array $resources): bool
    {
        foreach ($resources as $amount) {
            if ($amount > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function emptyResources(): array
    {
        return [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];
    }

    /**
     * @return list<string>
     */
    protected function resourceKeys(): array
    {
        return ['wood', 'clay', 'iron', 'crop'];
    }

    protected function headerValue(SessionResponse $response, string $headerName): ?string
    {
        foreach ($response->headers as $name => $values) {
            if (mb_strtolower($name) !== mb_strtolower($headerName)) {
                continue;
            }

            $value = $values[0] ?? null;

            return is_string($value) && $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $extraHeaders
     * @return array<string, mixed>
     */
    protected function xhrRequestOptions(?string $referer = null, array $extraHeaders = []): array
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

        return [
            'headers' => [
                ...$headers,
                ...$extraHeaders,
            ],
        ];
    }

    protected function absoluteUri(string $uri, Account $account): string
    {
        if (preg_match('/^https?:\/\//i', $uri) === 1) {
            return $uri;
        }

        return rtrim($account->server_url, '/').'/'.ltrim($uri, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logHeroResourceActivity(
        Account $account,
        Village $village,
        ActivityLogStatus $status,
        string $message,
        array $payload = [],
    ): void {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Hero,
            'status' => $status,
            'payload' => $payload,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
