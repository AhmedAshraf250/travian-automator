<?php

namespace App\Application\Accounts\Trading;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use App\Models\VillageBuilding;
use App\Models\VillageResourceState;
use App\Models\VillageSetting;

/**
 * Sends a user-requested marketplace shipment from one village to coordinates.
 */
class ExecuteManualMarketplaceTransfer
{
    private const string MARKETPLACE_SEND_ENDPOINT = '/api/v1/marketplace/resources/send';

    private const string VALIDATE_DESTINATION_ENDPOINT = '/api/v1/validate-destination';

    public function __construct(
        protected AccountSessionFactory $accountSessionFactory,
        protected TravianLoginAction $travianLoginAction,
    ) {}

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     */
    public function handle(Account $account, Village $sourceVillage, int $x, int $y, array $resources): void
    {
        $sourceVillage = $sourceVillage->fresh(['buildings', 'resourceState', 'settings']);

        if (! $sourceVillage instanceof Village || ! $sourceVillage->is_active || ! $account->is_active) {
            return;
        }

        $marketSlot = $sourceVillage->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 17);

        if (! $marketSlot instanceof VillageBuilding) {
            $this->logTransfer($account, $sourceVillage, ActivityLogStatus::Failed, 'Manual marketplace transfer failed: source village has no synced marketplace.', [
                'destination' => ['x' => $x, 'y' => $y],
                'resources' => $resources,
            ]);

            return;
        }

        if ($this->shipmentIsEmpty($resources)) {
            return;
        }

        $stockErrors = $this->stockErrors($sourceVillage, $resources);

        if ($stockErrors !== []) {
            $this->logTransfer($account, $sourceVillage, ActivityLogStatus::Failed, 'Manual marketplace transfer stopped: requested resources exceed the latest local village stock.', [
                'destination' => ['x' => $x, 'y' => $y],
                'resources' => $resources,
                'stock_errors' => $stockErrors,
            ]);

            return;
        }

        $session = $this->accountSessionFactory->for($account);
        $this->travianLoginAction->handle($account, $session);
        $marketReferer = $this->openMarketSendTab($account, $sourceVillage, $marketSlot, $session);

        $validation = $this->validateDestination($session, $marketReferer, $sourceVillage, $x, $y);

        if (! $validation['accepted']) {
            $this->logTransfer($account, $sourceVillage, ActivityLogStatus::Failed, 'Manual marketplace transfer destination rejected by Travian.', [
                'destination' => ['x' => $x, 'y' => $y],
                'resources' => $resources,
                'validation' => $validation,
            ]);
            $session->persist();

            return;
        }

        $result = $this->sendResources($sourceVillage, $session, $marketReferer, $x, $y, $resources);

        if (! $result['accepted']) {
            $message = ($result['blocked_reason'] ?? null) === 'duration_limit'
                ? 'Manual marketplace transfer stopped: merchant travel time is above the configured limit.'
                : 'Manual marketplace transfer was rejected by Travian.';

            $this->logTransfer($account, $sourceVillage, ActivityLogStatus::Failed, $message, [
                'destination' => ['x' => $x, 'y' => $y],
                'resources' => $resources,
                'validation' => $validation,
                'result' => $result,
            ]);
            $session->persist();

            return;
        }

        $destinationVillage = $this->resolveDestinationVillage($account, $sourceVillage, $x, $y);
        $destinationLabel = $this->formatDestinationLabel($destinationVillage, $x, $y);
        $merchantsUsed = $this->subtractLocalMerchants($sourceVillage, $resources);
        $this->subtractLocalResources($sourceVillage, $resources);
        $this->logTransfer($account, $sourceVillage, ActivityLogStatus::Done, "Manual marketplace transfer sent successfully: {$this->formatResourceShipment($resources)} to {$destinationLabel}.", [
            'destination' => [
                'x' => $x,
                'y' => $y,
                'village_id' => $destinationVillage?->id,
                'name' => $destinationVillage?->name,
            ],
            'resources' => $resources,
            'merchants_used' => $merchantsUsed,
            'validation' => $validation,
            'result' => $result,
        ]);
        $session->persist();
    }

    protected function openMarketSendTab(Account $account, Village $sourceVillage, VillageBuilding $marketSlot, AccountSession $session): string
    {
        $switchResponse = $session->get(
            $this->resolveVillageSwitchUri($sourceVillage),
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
     * @return array{accepted: bool, status_code: int, body: string|null, message: string|null}
     */
    protected function validateDestination(AccountSession $session, string $referer, Village $sourceVillage, int $x, int $y): array
    {
        $response = $session->postJson(self::VALIDATE_DESTINATION_ENDPOINT, [
            'sourceVillageId' => (int) $sourceVillage->travian_village_id,
            'destinationCoordinates' => [
                'x' => $x,
                'y' => $y,
            ],
            'context' => 'sendResources',
        ], $this->xhrRequestOptions($referer));

        $payload = json_decode($response->body, true);
        $message = is_array($payload) && isset($payload['message']) ? (string) $payload['message'] : null;

        return [
            'accepted' => $response->successful(),
            'status_code' => $response->statusCode,
            'body' => mb_substr($response->body, 0, 500),
            'message' => $message,
        ];
    }

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     * @return array{accepted: bool, preview_status_code: int, confirm_status_code: int|null, nonce_present: bool, duration_seconds: int|null, max_duration_seconds: int, blocked_reason: string|null, preview_body: string|null, confirm_body: string|null}
     */
    protected function sendResources(Village $sourceVillage, AccountSession $session, string $referer, int $x, int $y, array $resources): array
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
                'x' => $x,
                'y' => $y,
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
        $maxDurationSeconds = $this->maxTradeDurationSeconds($sourceVillage);

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

    protected function maxTradeDurationSeconds(Village $sourceVillage): int
    {
        $settings = $sourceVillage->settings;

        if ($settings instanceof VillageSetting && $settings->trade_max_duration_seconds !== null) {
            return max(60, (int) $settings->trade_max_duration_seconds);
        }

        return VillageSetting::defaultTradeMaxDurationSeconds();
    }

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     */
    protected function subtractLocalResources(Village $sourceVillage, array $resources): void
    {
        $resourceState = $sourceVillage->resourceState;

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
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     */
    protected function subtractLocalMerchants(Village $sourceVillage, array $resources): int
    {
        $resourceState = $sourceVillage->resourceState;

        if (! $resourceState instanceof VillageResourceState || $resourceState->available_merchants === null) {
            return 0;
        }

        $merchantCapacity = max(1, (int) ($resourceState->merchant_capacity ?? 500));
        $merchantsUsed = max(1, (int) ceil(array_sum($resources) / $merchantCapacity));

        $resourceState->forceFill([
            'available_merchants' => max(0, (int) $resourceState->available_merchants - $merchantsUsed),
            'server_reported_at' => now(),
        ])->save();

        return $merchantsUsed;
    }

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     */
    protected function formatResourceShipment(array $resources): string
    {
        $parts = [];

        foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
            $amount = max(0, (int) ($resources[$resource] ?? 0));

            if ($amount > 0) {
                $parts[] = "{$amount} {$resource}";
            }
        }

        return implode(', ', $parts);
    }

    protected function resolveDestinationVillage(Account $account, Village $sourceVillage, int $x, int $y): ?Village
    {
        return Village::query()
            ->where('account_id', $account->id)
            ->where('id', '!=', $sourceVillage->id)
            ->where('x', $x)
            ->where('y', $y)
            ->orderBy('name')
            ->first();
    }

    protected function formatDestinationLabel(?Village $destinationVillage, int $x, int $y): string
    {
        $coordinates = "[{$x}|{$y}]";

        if (! $destinationVillage instanceof Village || trim($destinationVillage->name) === '') {
            return $coordinates;
        }

        return "{$destinationVillage->name} {$coordinates}";
    }

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
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
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     * @return list<array{resource:string, requested:int, available:int}>
     */
    protected function stockErrors(Village $sourceVillage, array $resources): array
    {
        $resourceState = $sourceVillage->resourceState;

        if (! $resourceState instanceof VillageResourceState) {
            return [];
        }

        $errors = [];

        foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
            $requested = max(0, (int) ($resources[$resource] ?? 0));
            $available = max(0, (int) $resourceState->{$resource});

            if ($requested > $available) {
                $errors[] = [
                    'resource' => $resource,
                    'requested' => $requested,
                    'available' => $available,
                ];
            }
        }

        return $errors;
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

        return ['headers' => $headers];
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
    protected function logTransfer(Account $account, Village $sourceVillage, ActivityLogStatus $status, string $message, array $payload = []): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $sourceVillage->id,
            'activity_type' => ActivityType::Transfer,
            'status' => $status,
            'payload' => $payload,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
