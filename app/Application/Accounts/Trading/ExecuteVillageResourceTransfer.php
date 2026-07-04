<?php

namespace App\Application\Accounts\Trading;

use App\Application\Accounts\Construction\Data\BuildPageAnalysis;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Enums\VillageTradeMode;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use App\Models\VillageBuilding;
use App\Models\VillageResourceState;
use App\Models\VillageSetting;
use Throwable;

/**
 * Sends owned-village marketplace resources to unblock a construction shortage.
 */
class ExecuteVillageResourceTransfer
{
    private const int NEGATIVE_CROP_BUFFER_HOURS = 6;

    private const int NEGATIVE_CROP_DANGER_HOURS = 4;

    private const string GRAPHQL_ENDPOINT = '/api/v1/graphql';

    private const string MARKETPLACE_SEND_ENDPOINT = '/api/v1/marketplace/resources/send';

    private const string MERCHANTS_QUERY = '{ownPlayer{banInfo{type}goldFeatures{travianPlus{isActive}goldClub}village{id marketplace{merchantsInfo{capacity available}tradeShipsInfo{capacity available}}isShore hasHarbour resources{lumberStock clayStock ironStock cropStock}}}}}';

    /**
     * Try to send resources from the largest eligible owned villages.
     *
     * @param  array<string, mixed>  $constructionPayload
     */
    public function handle(
        Account $account,
        Village $recipientVillage,
        AccountSession $session,
        array $constructionPayload,
        BuildPageAnalysis $analysis,
    ): void {
        if (! $analysis->isResourceShortage()) {
            return;
        }

        $recipientVillage = $recipientVillage->fresh(['resourceState', 'settings']);

        if (! $recipientVillage instanceof Village || ! $recipientVillage->resourceState instanceof VillageResourceState) {
            return;
        }

        if ($recipientVillage->settings instanceof VillageSetting && ! (bool) $recipientVillage->settings->support_enabled) {
            $this->logTransferActivity($account, $recipientVillage, null, ActivityLogStatus::Pending, 'Recipient village is not configured to receive support resources.', [
                'construction' => $constructionPayload,
            ]);

            return;
        }

        $remainingResources = $this->roundedResourceShortages($recipientVillage, $analysis->requiredResources);
        $remainingResources = $this->filterNegativeCropSupplyResources($recipientVillage, $remainingResources);

        if ($this->shipmentIsEmpty($remainingResources)) {
            return;
        }

        $transferPurpose = (string) ($constructionPayload['queue_kind'] ?? '') === 'crop_support'
            ? 'negative crop support'
            : 'construction';
        $sentShipments = [];
        $lastBlockedReason = null;

        foreach ($this->supplierVillages($account, $recipientVillage) as $supplierVillage) {
            $marketSlot = $this->marketSlot($supplierVillage);

            if (! $marketSlot instanceof VillageBuilding) {
                continue;
            }

            $shipmentResources = $this->buildShipmentResources($supplierVillage, $remainingResources);

            if ($this->shipmentIsEmpty($shipmentResources)) {
                continue;
            }

            try {
                $marketReferer = $this->openSupplierMarket($account, $supplierVillage, $session, $marketSlot);
                $shipmentResources = $this->capShipmentToAvailableMerchants($supplierVillage, $session, $marketReferer, $shipmentResources);

                if ($this->shipmentIsEmpty($shipmentResources)) {
                    continue;
                }

                $result = $this->sendMarketplaceResources($supplierVillage, $recipientVillage, $session, $marketReferer, $shipmentResources);

                if (! $result['accepted']) {
                    $lastBlockedReason = $result['blocked_reason'] ?? null;
                    $message = ($result['blocked_reason'] ?? null) === 'duration_limit'
                        ? 'Resource transfer stopped: merchant travel time is above the configured limit.'
                        : 'Resource transfer was rejected by Travian.';

                    $this->logTransferActivity($account, $recipientVillage, $supplierVillage, ActivityLogStatus::Failed, $message, [
                        'construction' => $constructionPayload,
                        'resources' => $shipmentResources,
                        'result' => $result,
                    ]);

                    continue;
                }

                $this->subtractLocalSupplierResources($supplierVillage, $shipmentResources);
                $remainingResources = $this->subtractShipment($remainingResources, $shipmentResources);
                $sentShipments[] = [
                    'supplier_village_id' => $supplierVillage->id,
                    'supplier_name' => $supplierVillage->name,
                    'resources' => $shipmentResources,
                    'result' => $result,
                ];

                $this->logTransferActivity($account, $recipientVillage, $supplierVillage, ActivityLogStatus::Done, "Resource transfer sent for {$transferPurpose}.", [
                    'construction' => $constructionPayload,
                    'resources' => $shipmentResources,
                    'remaining_resources' => $remainingResources,
                    'result' => $result,
                ]);

                if ($this->shipmentIsEmpty($remainingResources)) {
                    return;
                }
            } catch (Throwable $throwable) {
                $this->logTransferActivity($account, $recipientVillage, $supplierVillage, ActivityLogStatus::Failed, 'Resource transfer automation failed: '.$throwable->getMessage(), [
                    'construction' => $constructionPayload,
                    'resources' => $shipmentResources,
                ]);
            }
        }

        if ($sentShipments === []) {
            if ($lastBlockedReason === 'duration_limit') {
                $this->logTransferActivity($account, $recipientVillage, null, ActivityLogStatus::Pending, "No supplier village could send resources within the configured travel time for {$transferPurpose}.", [
                    'construction' => $constructionPayload,
                    'needed_resources' => $remainingResources,
                ]);

                return;
            }

            $this->logTransferActivity($account, $recipientVillage, null, ActivityLogStatus::Pending, "No eligible supplier village could send resources for {$transferPurpose}.", [
                'construction' => $constructionPayload,
                'needed_resources' => $remainingResources,
            ]);

            return;
        }

        $this->logTransferActivity($account, $recipientVillage, null, ActivityLogStatus::Pending, "Resource transfer partially covered {$transferPurpose}.", [
            'construction' => $constructionPayload,
            'remaining_resources' => $remainingResources,
            'shipments' => $sentShipments,
        ]);
    }

    /**
     * Feed a village that is close to emptying its granary while crop production is negative.
     */
    public function supportNegativeCrop(Account $account, Village $recipientVillage, AccountSession $session): void
    {
        $recipientVillage = $recipientVillage->fresh(['resourceState', 'settings']);

        if (! $recipientVillage instanceof Village || ! $recipientVillage->resourceState instanceof VillageResourceState) {
            return;
        }

        $settings = $recipientVillage->settings;

        if (! $settings instanceof VillageSetting || ! (bool) $settings->support_enabled || ! (bool) $settings->supply_negative_crop_enabled) {
            return;
        }

        $resourceState = $recipientVillage->resourceState;
        $cropProduction = (int) $resourceState->crop_production;

        if ($cropProduction >= 0) {
            return;
        }

        $granaryCapacity = max(0, (int) $resourceState->granary_capacity);
        $currentCrop = max(0, (int) $resourceState->crop);
        $cropBurnPerHour = abs($cropProduction);

        if ($granaryCapacity <= 0 || $cropBurnPerHour <= 0) {
            return;
        }

        $dangerCropFloor = max(100, (int) floor($granaryCapacity * 0.25));
        $hoursUntilEmpty = $currentCrop > 0 ? $currentCrop / $cropBurnPerHour : 0.0;

        if ($currentCrop > $dangerCropFloor && $hoursUntilEmpty > self::NEGATIVE_CROP_DANGER_HOURS) {
            return;
        }

        $targetCrop = min(
            $granaryCapacity,
            max($dangerCropFloor, $cropBurnPerHour * self::NEGATIVE_CROP_BUFFER_HOURS),
        );

        if ($targetCrop <= $currentCrop) {
            return;
        }

        $this->handle(
            $account,
            $recipientVillage,
            $session,
            [
                'queue_kind' => 'crop_support',
                'building_name' => 'Negative crop support',
                'crop_production' => $cropProduction,
                'current_crop' => $currentCrop,
                'target_crop' => $targetCrop,
            ],
            new BuildPageAnalysis(
                actionUri: null,
                requiredResources: [
                    'wood' => 0,
                    'clay' => 0,
                    'iron' => 0,
                    'crop' => $targetCrop,
                ],
                blockedReason: 'resource_shortage',
                blockedMessage: 'negative crop support',
                resourceReadySeconds: null,
                resourceReadyLabel: null,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $requiredResources
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function roundedResourceShortages(Village $recipientVillage, array $requiredResources): array
    {
        $resourceState = $recipientVillage->resourceState;

        if (! $resourceState instanceof VillageResourceState) {
            return [
                'wood' => 0,
                'clay' => 0,
                'iron' => 0,
                'crop' => 0,
            ];
        }

        $shortages = [];

        foreach ($this->resourceKeys() as $resourceKey) {
            $missingAmount = max(0, (int) ($requiredResources[$resourceKey] ?? 0) - (int) $resourceState->{$resourceKey});
            $shortages[$resourceKey] = $missingAmount > 0
                ? (int) (ceil($missingAmount / 100) * 100)
                : 0;
        }

        return $shortages;
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $remainingResources
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function filterNegativeCropSupplyResources(Village $recipientVillage, array $remainingResources): array
    {
        if ((int) ($recipientVillage->resourceState?->crop_production ?? 0) >= 0) {
            return $remainingResources;
        }

        if (! $recipientVillage->settings instanceof VillageSetting || (bool) $recipientVillage->settings->supply_negative_crop_enabled) {
            return $remainingResources;
        }

        $remainingResources['crop'] = 0;

        return $remainingResources;
    }

    /**
     * @return list<Village>
     */
    protected function supplierVillages(Account $account, Village $recipientVillage): array
    {
        return Village::query()
            ->with([
                'settings',
                'resourceState',
                'buildings' => fn ($query) => $query->orderBy('slot_id'),
            ])
            ->where('account_id', $account->id)
            ->where('id', '!=', $recipientVillage->id)
            ->where('is_active', true)
            ->orderByDesc('population')
            ->orderBy('id')
            ->get()
            ->filter(function (Village $village): bool {
                $settings = $village->settings;

                return $settings instanceof VillageSetting
                    && $village->resourceState instanceof VillageResourceState
                    && (bool) $settings->send_enabled
                    && $settings->trade_mode !== VillageTradeMode::Paused;
            })
            ->values()
            ->all();
    }

    protected function marketSlot(Village $supplierVillage): ?VillageBuilding
    {
        return $supplierVillage->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 17);
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $remainingResources
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function buildShipmentResources(Village $supplierVillage, array $remainingResources): array
    {
        $settings = $supplierVillage->settings;
        $resourceState = $supplierVillage->resourceState;

        if (! $settings instanceof VillageSetting || ! $resourceState instanceof VillageResourceState) {
            return $this->emptyShipment();
        }

        $minPercentage = max(0, min(100, (int) ($settings->send_min_resource_percentage ?? 30)));
        $reservePercentage = max(0, min(100, (int) ($settings->send_reserve_resource_percentage ?? 10)));
        $resources = [];

        foreach ($this->resourceKeys() as $resourceKey) {
            $capacity = $this->capacityForResource($resourceState, $resourceKey);
            $currentAmount = max(0, (int) $resourceState->{$resourceKey});
            $minimumRequired = (int) floor($capacity * ($minPercentage / 100));

            if ($capacity <= 0 || $currentAmount < $minimumRequired) {
                $resources[$resourceKey] = 0;

                continue;
            }

            $reserveAmount = (int) floor($capacity * ($reservePercentage / 100));
            $availableAmount = max(0, $currentAmount - $reserveAmount);
            $resources[$resourceKey] = $this->roundDownToHundreds(min($availableAmount, $remainingResources[$resourceKey]));
        }

        return $resources;
    }

    protected function openSupplierMarket(
        Account $account,
        Village $supplierVillage,
        AccountSession $session,
        VillageBuilding $marketSlot,
    ): string {
        $switchResponse = $session->get(
            $this->resolveVillageSwitchUri($supplierVillage),
            $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)),
        );
        $villageCenterResponse = $session->get(
            (string) config('travian.paths.village_center', '/dorf2.php'),
            $this->documentRequestOptions($switchResponse->effectiveUri),
        );
        $marketUri = (string) config('travian.paths.build', '/build.php')
            .'?id='.(int) $marketSlot->slot_id.'&gid=17';
        $marketResponse = $session->get($marketUri, $this->documentRequestOptions($villageCenterResponse->effectiveUri));
        $sendTabResponse = $session->get($marketUri.'&t=5', $this->documentRequestOptions($marketResponse->effectiveUri));

        return $sendTabResponse->effectiveUri;
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $resources
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function capShipmentToAvailableMerchants(Village $supplierVillage, AccountSession $session, string $referer, array $resources): array
    {
        $response = $session->postJson(self::GRAPHQL_ENDPOINT, [
            'query' => self::MERCHANTS_QUERY,
        ], $this->xhrRequestOptions($referer));

        if (! $response->successful()) {
            return $resources;
        }

        $payload = json_decode($response->body, true);

        if (! is_array($payload)) {
            return $resources;
        }

        $merchantsInfo = $payload['data']['ownPlayer']['village']['marketplace']['merchantsInfo'] ?? null;

        if (! is_array($merchantsInfo)) {
            return $resources;
        }

        $merchantCapacity = max(0, (int) ($merchantsInfo['capacity'] ?? 0));
        $availableMerchants = max(0, (int) ($merchantsInfo['available'] ?? 0));

        $supplierVillage->resourceState()->updateOrCreate(
            ['village_id' => $supplierVillage->id],
            [
                'available_merchants' => $availableMerchants,
                'merchant_capacity' => $merchantCapacity > 0 ? $merchantCapacity : null,
            ],
        );

        $availableCapacity = $merchantCapacity * $availableMerchants;

        if ($availableCapacity <= 0) {
            return $this->emptyShipment();
        }

        return $this->trimShipmentToCapacity($resources, $availableCapacity);
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $resources
     * @return array{accepted: bool, preview_status_code: int, confirm_status_code: int|null, nonce_present: bool, duration_seconds: int|null, max_duration_seconds: int, blocked_reason: string|null, preview_body: string|null, confirm_body: string|null}
     */
    protected function sendMarketplaceResources(Village $supplierVillage, Village $recipientVillage, AccountSession $session, string $referer, array $resources): array
    {
        $payload = [
            'action' => 'marketPlace',
            'resources' => [
                'lumber' => $resources['wood'],
                'clay' => $resources['clay'],
                'iron' => $resources['iron'],
                'crop' => $resources['crop'],
            ],
            'destination' => [
                'x' => (int) $recipientVillage->x,
                'y' => (int) $recipientVillage->y,
            ],
            'runs' => 1,
            'useTradeShips' => false,
        ];

        $previewResponse = $session->putJson(self::MARKETPLACE_SEND_ENDPOINT, $payload, $this->xhrRequestOptions($referer));
        $nonce = $this->headerValue($previewResponse, 'x-nonce');
        $previewPayload = json_decode($previewResponse->body, true);
        $durationSeconds = is_array($previewPayload) && isset($previewPayload['duration'])
            ? max(0, (int) $previewPayload['duration'])
            : null;
        $maxDurationSeconds = $this->maxTradeDurationSeconds($supplierVillage);

        if (! $previewResponse->successful() || $nonce === null) {
            return [
                'accepted' => false,
                'preview_status_code' => $previewResponse->statusCode,
                'confirm_status_code' => null,
                'nonce_present' => $nonce !== null,
                'duration_seconds' => $durationSeconds,
                'max_duration_seconds' => $maxDurationSeconds,
                'blocked_reason' => null,
                'preview_body' => mb_substr($previewResponse->body, 0, 500),
                'confirm_body' => null,
            ];
        }

        if ($durationSeconds !== null && $durationSeconds > $maxDurationSeconds) {
            return [
                'accepted' => false,
                'preview_status_code' => $previewResponse->statusCode,
                'confirm_status_code' => null,
                'nonce_present' => true,
                'duration_seconds' => $durationSeconds,
                'max_duration_seconds' => $maxDurationSeconds,
                'blocked_reason' => 'duration_limit',
                'preview_body' => mb_substr($previewResponse->body, 0, 500),
                'confirm_body' => null,
            ];
        }

        $confirmOptions = $this->xhrRequestOptions($referer);
        $confirmOptions['headers']['x-nonce'] = $nonce;
        $confirmResponse = $session->postJson(self::MARKETPLACE_SEND_ENDPOINT, $payload, $confirmOptions);

        return [
            'accepted' => $confirmResponse->successful(),
            'preview_status_code' => $previewResponse->statusCode,
            'confirm_status_code' => $confirmResponse->statusCode,
            'nonce_present' => true,
            'duration_seconds' => $durationSeconds,
            'max_duration_seconds' => $maxDurationSeconds,
            'blocked_reason' => null,
            'preview_body' => mb_substr($previewResponse->body, 0, 500),
            'confirm_body' => mb_substr($confirmResponse->body, 0, 500),
        ];
    }

    protected function maxTradeDurationSeconds(Village $supplierVillage): int
    {
        $settings = $supplierVillage->settings;

        if ($settings instanceof VillageSetting && $settings->trade_max_duration_seconds !== null) {
            return max(60, (int) $settings->trade_max_duration_seconds);
        }

        return VillageSetting::defaultTradeMaxDurationSeconds();
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $resources
     */
    protected function subtractLocalSupplierResources(Village $supplierVillage, array $resources): void
    {
        $resourceState = $supplierVillage->resourceState;

        if (! $resourceState instanceof VillageResourceState) {
            return;
        }

        $resourceState->forceFill([
            'wood' => max(0, (int) $resourceState->wood - $resources['wood']),
            'clay' => max(0, (int) $resourceState->clay - $resources['clay']),
            'iron' => max(0, (int) $resourceState->iron - $resources['iron']),
            'crop' => max(0, (int) $resourceState->crop - $resources['crop']),
            'server_reported_at' => now(),
        ])->save();
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $remainingResources
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $shipmentResources
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function subtractShipment(array $remainingResources, array $shipmentResources): array
    {
        return [
            'wood' => max(0, $remainingResources['wood'] - $shipmentResources['wood']),
            'clay' => max(0, $remainingResources['clay'] - $shipmentResources['clay']),
            'iron' => max(0, $remainingResources['iron'] - $shipmentResources['iron']),
            'crop' => max(0, $remainingResources['crop'] - $shipmentResources['crop']),
        ];
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $resources
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function trimShipmentToCapacity(array $resources, int $availableCapacity): array
    {
        $remainingCapacity = $this->roundDownToHundreds($availableCapacity);
        $trimmedResources = $this->emptyShipment();

        foreach ($this->resourceKeys() as $resourceKey) {
            if ($remainingCapacity <= 0) {
                break;
            }

            $amount = min($resources[$resourceKey], $remainingCapacity);
            $amount = $this->roundDownToHundreds($amount);
            $trimmedResources[$resourceKey] = $amount;
            $remainingCapacity -= $amount;
        }

        return $trimmedResources;
    }

    protected function capacityForResource(VillageResourceState $resourceState, string $resourceKey): int
    {
        return $resourceKey === 'crop'
            ? max(0, (int) $resourceState->granary_capacity)
            : max(0, (int) $resourceState->warehouse_capacity);
    }

    protected function roundDownToHundreds(int $amount): int
    {
        return (int) (floor(max(0, $amount) / 100) * 100);
    }

    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $resources
     */
    protected function shipmentIsEmpty(array $resources): bool
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
    protected function emptyShipment(): array
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

        return [
            'headers' => $headers,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => false,
            ],
        ];
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

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logTransferActivity(
        Account $account,
        Village $recipientVillage,
        ?Village $supplierVillage,
        ActivityLogStatus $status,
        string $message,
        array $payload = [],
    ): void {
        if ($supplierVillage instanceof Village) {
            $payload['supplier_village_id'] = $supplierVillage->id;
            $payload['supplier_village_name'] = $supplierVillage->name;
        }

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $recipientVillage->id,
            'activity_type' => ActivityType::Transfer,
            'status' => $status,
            'payload' => $payload,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
