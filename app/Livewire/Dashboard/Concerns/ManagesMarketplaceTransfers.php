<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Jobs\RefreshVillageMarketplaceSnapshotJob;
use App\Jobs\SendManualMarketplaceTransferJob;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageBuilding;
use App\Models\VillageResourceState;
use App\Models\VillageSetting;
use Illuminate\Support\Collection;

trait ManagesMarketplaceTransfers
{
    use HasVillageTradeDrafts;

    public bool $showMarketplaceTransferModal = false;

    public string $marketplaceTransferTab = 'send';

    public ?int $marketplaceSourceVillageId = null;

    public string $marketplaceSourceVillageLabel = '';

    public string $marketplaceDestinationMode = 'owned';

    public ?int $marketplaceDestinationVillageId = null;

    public string $marketplaceDestinationX = '';

    public string $marketplaceDestinationY = '';

    public int $marketplaceWoodDraft = 0;

    public int $marketplaceClayDraft = 0;

    public int $marketplaceIronDraft = 0;

    public int $marketplaceCropDraft = 0;

    /**
     * Temporarily polls the TR capacity panel after a manual refresh is queued.
     */
    public ?int $marketplaceSnapshotRefreshPollUntil = null;

    public function openMarketplaceTransferModal(int $villageId): void
    {
        $village = Village::query()
            ->with(['account.villages', 'buildings', 'resourceState', 'runtimeState', 'settings'])
            ->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);

        $this->marketplaceSourceVillageId = $village->id;
        $this->marketplaceSourceVillageLabel = trim($village->name.' ['.$village->x.'|'.$village->y.']');
        $this->marketplaceTransferTab = 'send';
        $this->marketplaceDestinationMode = 'owned';
        $this->marketplaceDestinationVillageId = $village->account->villages
            ->where('id', '!=', $village->id)
            ->sortBy('name')
            ->first()?->id;
        $this->marketplaceDestinationX = '';
        $this->marketplaceDestinationY = '';
        $this->marketplaceWoodDraft = 0;
        $this->marketplaceClayDraft = 0;
        $this->marketplaceIronDraft = 0;
        $this->marketplaceCropDraft = 0;
        $this->marketplaceSnapshotRefreshPollUntil = null;
        $this->villageSendResourcesDraft = (bool) $settings->send_enabled;
        $this->villageSupplyResourcesDraft = (bool) $settings->support_enabled;
        $this->villageSupplyNegativeCropDraft = (bool) $settings->supply_negative_crop_enabled;
        $this->villageSendMinResourcePercentageDraft = max(0, min(100, (int) ($settings->send_min_resource_percentage ?? 30)));
        $this->villageSendReserveResourcePercentageDraft = max(0, min(100, (int) ($settings->send_reserve_resource_percentage ?? 10)));
        $this->villageTradeMaxDurationMinutesDraft = $this->secondsToWholeMinutes(
            (int) ($settings->trade_max_duration_seconds ?? VillageSetting::defaultTradeMaxDurationSeconds()),
        );

        $this->showMarketplaceTransferModal = true;
    }

    public function refreshMarketplaceSnapshot(): void
    {
        $village = $this->marketplaceSourceVillageId !== null
            ? Village::query()->with(['account', 'buildings', 'runtimeState'])->find($this->marketplaceSourceVillageId)
            : null;

        if (! $village instanceof Village || ! $village->account instanceof Account || ! $this->marketplaceCapacityCanRefresh($village)) {
            session()->flash('dashboard-banner', 'Marketplace snapshot could not be queued. Check automation, village status, and marketplace sync.');

            return;
        }

        RefreshVillageMarketplaceSnapshotJob::dispatch($village->account_id, $village->id);
        $this->marketplaceSnapshotRefreshPollUntil = now()->addSeconds(90)->getTimestamp();

        session()->flash('dashboard-banner', "{$village->name}: marketplace snapshot refresh queued. The TR panel will update when the result is saved.");
    }

    public function refreshMarketplaceTransferCapacityView(): void
    {
        if (! $this->showMarketplaceTransferModal || $this->marketplaceTransferTab !== 'send') {
            $this->skipRender();

            return;
        }

        if ($this->marketplaceSnapshotRefreshPollUntil === null || now()->getTimestamp() > $this->marketplaceSnapshotRefreshPollUntil) {
            $this->marketplaceSnapshotRefreshPollUntil = null;
            $this->skipRender();
        }
    }

    public function updatedMarketplaceWoodDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('wood');
    }

    public function updatedMarketplaceClayDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('clay');
    }

    public function updatedMarketplaceIronDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('iron');
    }

    public function updatedMarketplaceCropDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('crop');
    }

    public function adjustMarketplaceResourceDraft(string $resource, int $direction): void
    {
        $property = [
            'wood' => 'marketplaceWoodDraft',
            'clay' => 'marketplaceClayDraft',
            'iron' => 'marketplaceIronDraft',
            'crop' => 'marketplaceCropDraft',
        ][$resource] ?? null;

        if ($property === null) {
            return;
        }

        $capacity = $this->marketplaceTransferCapacity();
        $step = max(1, (int) ($capacity['merchant_capacity'] ?? $this->merchantCapacityForTribe(null)));
        $this->{$property} = max(0, (int) $this->{$property} + ($direction >= 0 ? $step : -$step));

        $this->clampMarketplaceResourceDrafts($resource);
    }

    public function setMarketplaceTransferTab(string $tab): void
    {
        if (! in_array($tab, ['send', 'settings'], true)) {
            return;
        }

        $this->marketplaceTransferTab = $tab;
    }

    public function closeMarketplaceTransferModal(): void
    {
        $this->showMarketplaceTransferModal = false;
        $this->marketplaceTransferTab = 'send';
        $this->marketplaceSourceVillageId = null;
        $this->marketplaceSourceVillageLabel = '';
        $this->marketplaceDestinationVillageId = null;
        $this->marketplaceDestinationMode = 'owned';
        $this->marketplaceDestinationX = '';
        $this->marketplaceDestinationY = '';
        $this->marketplaceWoodDraft = 0;
        $this->marketplaceClayDraft = 0;
        $this->marketplaceIronDraft = 0;
        $this->marketplaceCropDraft = 0;
        $this->marketplaceSnapshotRefreshPollUntil = null;
    }

    public function saveMarketplaceTradeSettings(): void
    {
        $this->validate([
            'marketplaceSourceVillageId' => ['required', 'integer', 'exists:villages,id'],
            'villageSendResourcesDraft' => ['boolean'],
            'villageSupplyResourcesDraft' => ['boolean'],
            'villageSupplyNegativeCropDraft' => ['boolean'],
            'villageSendMinResourcePercentageDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'villageSendReserveResourcePercentageDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'villageTradeMaxDurationMinutesDraft' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);

        $village = Village::query()
            ->with(['account', 'settings'])
            ->findOrFail((int) $this->marketplaceSourceVillageId);

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);

        $settings->forceFill([
            'send_enabled' => $this->villageSendResourcesDraft,
            'support_enabled' => $this->villageSupplyResourcesDraft,
            'supply_negative_crop_enabled' => $this->villageSupplyResourcesDraft && $this->villageSupplyNegativeCropDraft,
            'send_min_resource_percentage' => max(0, min(100, (int) $this->villageSendMinResourcePercentageDraft)),
            'send_reserve_resource_percentage' => max(0, min(100, (int) $this->villageSendReserveResourcePercentageDraft)),
            'trade_max_duration_seconds' => $this->minutesToSeconds($this->villageTradeMaxDurationMinutesDraft),
        ])->save();

        $this->logManualActivity($village->account, $village, 'Village trade settings updated from TR panel.');
        $this->dashboardRevision = '';
        session()->flash('dashboard-banner', "{$village->name}: trade settings saved.");
    }

    public function queueManualMarketplaceTransfer(): void
    {
        $sourceVillage = $this->marketplaceSourceVillageId !== null
            ? Village::query()->with(['account', 'resourceState', 'runtimeState'])->find($this->marketplaceSourceVillageId)
            : null;

        if (! $sourceVillage instanceof Village || ! $sourceVillage->account instanceof Account) {
            return;
        }

        if ($this->marketplaceDestinationMode === 'owned') {
            $destinationVillage = $this->marketplaceDestinationVillageId !== null
                ? Village::query()
                    ->where('account_id', $sourceVillage->account_id)
                    ->find($this->marketplaceDestinationVillageId)
                : null;

            if (! $destinationVillage instanceof Village || $destinationVillage->x === null || $destinationVillage->y === null) {
                session()->flash('dashboard-banner', 'Choose a destination village with known coordinates.');

                return;
            }

            $x = (int) $destinationVillage->x;
            $y = (int) $destinationVillage->y;
        } else {
            $x = (int) $this->marketplaceDestinationX;
            $y = (int) $this->marketplaceDestinationY;
        }

        $resources = [
            'wood' => max(0, (int) $this->marketplaceWoodDraft),
            'clay' => max(0, (int) $this->marketplaceClayDraft),
            'iron' => max(0, (int) $this->marketplaceIronDraft),
            'crop' => max(0, (int) $this->marketplaceCropDraft),
        ];

        if (array_sum($resources) <= 0) {
            session()->flash('dashboard-banner', 'Enter at least one resource amount to send.');

            return;
        }

        $stockErrors = $this->marketplaceStockErrors($sourceVillage, $resources);

        if ($stockErrors !== []) {
            session()->flash('dashboard-banner', 'Cannot queue transfer: '.implode(', ', $stockErrors).'.');

            return;
        }

        $capacity = $this->marketplaceTransferCapacity($sourceVillage);
        $totalResources = array_sum($resources);

        if (($capacity['total_capacity'] ?? null) !== null && $totalResources > (int) $capacity['total_capacity']) {
            session()->flash(
                'dashboard-banner',
                "Cannot queue transfer: available merchants can carry {$capacity['total_capacity']} resources, but you entered {$totalResources}.",
            );

            return;
        }

        ActivityLog::query()->create([
            'account_id' => $sourceVillage->account_id,
            'village_id' => $sourceVillage->id,
            'activity_type' => ActivityType::Transfer,
            'status' => ActivityLogStatus::Pending,
            'payload' => [
                'destination' => ['x' => $x, 'y' => $y],
                'resources' => $resources,
            ],
            'message' => 'Manual marketplace transfer queued from dashboard.',
            'scheduled_at' => now(),
        ]);

        SendManualMarketplaceTransferJob::dispatch($sourceVillage->account_id, $sourceVillage->id, $x, $y, $resources);

        $this->closeMarketplaceTransferModal();
        $this->dashboardRevision = '';
        session()->flash('dashboard-banner', "Marketplace transfer from {$sourceVillage->name} was queued.");
    }

    /**
     * @return Collection<int, Village>
     */
    protected function marketplaceTransferVillages(): Collection
    {
        if (! $this->showMarketplaceTransferModal || $this->marketplaceSourceVillageId === null) {
            return collect();
        }

        $sourceVillage = Village::query()->find($this->marketplaceSourceVillageId);

        if (! $sourceVillage instanceof Village) {
            return collect();
        }

        return Village::query()
            ->where('account_id', $sourceVillage->account_id)
            ->where('id', '!=', $sourceVillage->id)
            ->whereNotNull('x')
            ->whereNotNull('y')
            ->orderBy('name')
            ->get();
    }

    /**
     * Build the marketplace capacity snapshot for the open TR modal.
     *
     * @return array{available_merchants:int|null, merchant_capacity:int, total_capacity:int|null, resources: array{wood:int|null, clay:int|null, iron:int|null, crop:int|null}, reported_at: string|null}
     */
    protected function marketplaceTransferCapacity(?Village $sourceVillage = null): array
    {
        $sourceVillage ??= $this->marketplaceSourceVillageId !== null
            ? Village::query()->with(['resourceState', 'runtimeState'])->find($this->marketplaceSourceVillageId)
            : null;

        $fallbackCapacity = $this->merchantCapacityForTribe(
            $sourceVillage instanceof Village && $sourceVillage->runtimeState?->tribe_id !== null
                ? (int) $sourceVillage->runtimeState->tribe_id
                : null,
        );

        if (! $sourceVillage instanceof Village || ! $sourceVillage->resourceState instanceof VillageResourceState) {
            return [
                'available_merchants' => null,
                'merchant_capacity' => $fallbackCapacity,
                'total_capacity' => null,
                'resources' => [
                    'wood' => null,
                    'clay' => null,
                    'iron' => null,
                    'crop' => null,
                ],
                'reported_at' => null,
            ];
        }

        $availableMerchants = $sourceVillage->resourceState->available_merchants !== null
            ? max(0, (int) $sourceVillage->resourceState->available_merchants)
            : null;
        $merchantCapacity = $sourceVillage->resourceState->merchant_capacity !== null
            ? max(1, (int) $sourceVillage->resourceState->merchant_capacity)
            : $fallbackCapacity;

        return [
            'available_merchants' => $availableMerchants,
            'merchant_capacity' => $merchantCapacity,
            'total_capacity' => $availableMerchants !== null ? $availableMerchants * $merchantCapacity : null,
            'resources' => [
                'wood' => max(0, (int) $sourceVillage->resourceState->wood),
                'clay' => max(0, (int) $sourceVillage->resourceState->clay),
                'iron' => max(0, (int) $sourceVillage->resourceState->iron),
                'crop' => max(0, (int) $sourceVillage->resourceState->crop),
            ],
            'reported_at' => $sourceVillage->resourceState->server_reported_at?->diffForHumans(),
        ];
    }

    protected function marketplaceCapacityCanRefresh(Village $village): bool
    {
        if (! SystemSetting::automationEnabled() || ! $village->is_active || ! $village->account?->is_active) {
            return false;
        }

        $marketSlot = $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 17);

        if (! $marketSlot instanceof VillageBuilding) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     * @return list<string>
     */
    protected function marketplaceStockErrors(Village $sourceVillage, array $resources): array
    {
        if (! $sourceVillage->resourceState instanceof VillageResourceState) {
            return [];
        }

        $labels = [
            'wood' => 'Wood',
            'clay' => 'Clay',
            'iron' => 'Iron',
            'crop' => 'Crop',
        ];
        $errors = [];

        foreach ($labels as $resource => $label) {
            $requested = max(0, (int) ($resources[$resource] ?? 0));
            $available = max(0, (int) $sourceVillage->resourceState->{$resource});

            if ($requested > $available) {
                $errors[] = "{$label} {$requested} exceeds current stock {$available}";
            }
        }

        return $errors;
    }

    protected function clampMarketplaceResourceDrafts(string $changedResource): void
    {
        $draftPropertyByResource = [
            'wood' => 'marketplaceWoodDraft',
            'clay' => 'marketplaceClayDraft',
            'iron' => 'marketplaceIronDraft',
            'crop' => 'marketplaceCropDraft',
        ];

        foreach ($draftPropertyByResource as $property) {
            $this->{$property} = max(0, (int) $this->{$property});
        }

        $capacity = $this->marketplaceTransferCapacity();
        $stockByResource = $capacity['resources'] ?? [];

        foreach ($draftPropertyByResource as $resource => $property) {
            if (($stockByResource[$resource] ?? null) === null) {
                continue;
            }

            $this->{$property} = min((int) $this->{$property}, max(0, (int) $stockByResource[$resource]));
        }

        $totalCapacity = $capacity['total_capacity'] ?? null;

        if ($totalCapacity === null || ! isset($draftPropertyByResource[$changedResource])) {
            return;
        }

        $totalResources = array_sum(array_map(fn (string $property): int => (int) $this->{$property}, $draftPropertyByResource));

        if ($totalResources <= (int) $totalCapacity) {
            return;
        }

        $changedProperty = $draftPropertyByResource[$changedResource];
        $overflow = $totalResources - (int) $totalCapacity;
        $this->{$changedProperty} = max(0, (int) $this->{$changedProperty} - $overflow);
    }

    protected function merchantCapacityForTribe(?int $tribeId): int
    {
        $capacity = (array) config('travian.game.merchant_capacity', []);

        return match ($tribeId) {
            1 => (int) ($capacity['roman'] ?? 500),
            2 => (int) ($capacity['teuton'] ?? 1000),
            3 => (int) ($capacity['gaul'] ?? 750),
            default => (int) ($capacity['roman'] ?? 500),
        };
    }

    protected function secondsToWholeMinutes(int $seconds): int
    {
        return max(1, (int) ceil(max(60, $seconds) / 60));
    }

    protected function minutesToSeconds(int $minutes): int
    {
        return max(1, min(10080, $minutes)) * 60;
    }

    public function formatTradeDurationMinutes(int $minutes): string
    {
        $minutes = max(1, $minutes);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return "{$remainingMinutes}m";
        }

        if ($remainingMinutes <= 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }
}
