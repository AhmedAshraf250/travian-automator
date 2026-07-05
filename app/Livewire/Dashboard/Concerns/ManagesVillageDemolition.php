<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Application\Travian\TravianBuildingCatalog;
use App\Jobs\CancelVillageDemolitionJob;
use App\Jobs\DemolishVillageBuildingJob;
use App\Jobs\RefreshVillageDemolitionSnapshotJob;
use App\Models\Account;
use App\Models\Village;
use App\Models\VillageBuilding;
use Illuminate\Support\Collection;

trait ManagesVillageDemolition
{
    public bool $showVillageDemolitionModal = false;

    public ?int $demolitionVillageId = null;

    public string $demolitionVillageLabel = '';

    public ?int $demolitionSelectedSlotId = null;

    public function openVillageDemolitionModal(int $villageId): void
    {
        $village = Village::query()
            ->with(['account', 'buildings', 'runtimeState'])
            ->findOrFail($villageId);

        $this->demolitionVillageId = $village->id;
        $this->demolitionVillageLabel = trim($village->name.' ['.$village->x.'|'.$village->y.']');
        $this->demolitionSelectedSlotId = $this->defaultDemolitionSlotId($village);
        $this->showVillageDemolitionModal = true;
    }

    public function closeVillageDemolitionModal(): void
    {
        $this->showVillageDemolitionModal = false;
        $this->demolitionVillageId = null;
        $this->demolitionVillageLabel = '';
        $this->demolitionSelectedSlotId = null;
    }

    public function refreshVillageDemolitionSnapshot(): void
    {
        $village = $this->demolitionVillage();

        if (! $village instanceof Village || ! $village->account instanceof Account) {
            return;
        }

        RefreshVillageDemolitionSnapshotJob::dispatch($village->account_id, $village->id);

        session()->flash('dashboard-banner', "{$village->name}: demolition snapshot refresh queued. Wait for the activity result, then reopen or refresh the panel.");
    }

    public function queueVillageBuildingDemolition(): void
    {
        $village = $this->demolitionVillage();
        $slotId = (int) $this->demolitionSelectedSlotId;

        if (! $village instanceof Village || ! $village->account instanceof Account || $slotId < 19 || $slotId > 40) {
            session()->flash('dashboard-banner', 'Choose a building to demolish.');

            return;
        }

        $mainBuildingLevel = $this->localMainBuildingLevel($village);

        if ($mainBuildingLevel < 10) {
            session()->flash('dashboard-banner', "Cannot demolish yet: Main Building is level {$mainBuildingLevel}, and level 10 is required.");

            return;
        }

        DemolishVillageBuildingJob::dispatch($village->account_id, $village->id, $slotId);

        $this->closeVillageDemolitionModal();
        session()->flash('dashboard-banner', "{$village->name}: building demolition queued.");
    }

    public function queueCancelVillageDemolition(): void
    {
        $village = $this->demolitionVillage();
        $activeDemolition = $this->demolitionSnapshot()['active'] ?? null;
        $cancelUri = is_array($activeDemolition) ? (string) ($activeDemolition['cancel_uri'] ?? '') : '';

        if (! $village instanceof Village || ! $village->account instanceof Account || $cancelUri === '') {
            session()->flash('dashboard-banner', 'No active demolition cancel link is available yet.');

            return;
        }

        CancelVillageDemolitionJob::dispatch($village->account_id, $village->id, $cancelUri);

        $this->closeVillageDemolitionModal();
        session()->flash('dashboard-banner', "{$village->name}: demolition cancel queued.");
    }

    protected function demolitionVillage(): ?Village
    {
        return $this->demolitionVillageId !== null
            ? Village::query()->with(['account', 'buildings', 'runtimeState'])->find($this->demolitionVillageId)
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function demolitionSnapshot(): array
    {
        if (! $this->showVillageDemolitionModal) {
            return [];
        }

        $village = $this->demolitionVillage();
        $snapshot = $village?->runtimeState?->demolition_entries;

        return is_array($snapshot) ? $snapshot : [
            'main_building_level' => $village instanceof Village ? $this->localMainBuildingLevel($village) : null,
            'available_buildings' => [],
            'active' => null,
            'recorded_at' => null,
        ];
    }

    /**
     * @return Collection<int, array{slot_id:int,name:string,level:int}>
     */
    protected function demolitionBuildings(): Collection
    {
        if (! $this->showVillageDemolitionModal) {
            return collect();
        }

        $snapshotBuildings = collect($this->demolitionSnapshot()['available_buildings'] ?? [])
            ->filter(static fn (mixed $building): bool => is_array($building))
            ->map(static fn (array $building): array => [
                'slot_id' => (int) ($building['slot_id'] ?? 0),
                'name' => (string) ($building['name'] ?? 'Building'),
                'level' => (int) ($building['level'] ?? 0),
            ])
            ->filter(static fn (array $building): bool => $building['slot_id'] >= 19 && $building['slot_id'] <= 40 && $building['level'] > 0)
            ->values();

        if ($snapshotBuildings->isNotEmpty()) {
            return $snapshotBuildings;
        }

        $village = $this->demolitionVillage();

        if (! $village instanceof Village) {
            return collect();
        }

        return $village->buildings
            ->filter(static fn (VillageBuilding $building): bool => (int) $building->slot_id >= 19
                && (int) $building->slot_id <= 40
                && (int) $building->building_gid > 0
                && (int) $building->current_level > 0)
            ->sortBy('slot_id')
            ->map(static fn (VillageBuilding $building): array => [
                'slot_id' => (int) $building->slot_id,
                'name' => $building->building_type ?: (TravianBuildingCatalog::nameForGid((int) $building->building_gid) ?? 'Building'),
                'level' => (int) $building->current_level,
            ])
            ->values();
    }

    protected function defaultDemolitionSlotId(Village $village): ?int
    {
        $firstBuilding = $village->buildings
            ->filter(static fn (VillageBuilding $building): bool => (int) $building->slot_id >= 19
                && (int) $building->slot_id <= 40
                && (int) $building->building_gid > 0
                && (int) $building->current_level > 0
                && (int) $building->building_gid !== 15)
            ->sortBy('slot_id')
            ->first();

        return $firstBuilding instanceof VillageBuilding ? (int) $firstBuilding->slot_id : null;
    }

    protected function localMainBuildingLevel(Village $village): int
    {
        $mainBuilding = $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 15);

        return $mainBuilding instanceof VillageBuilding ? max(0, (int) $mainBuilding->current_level) : 0;
    }
}
