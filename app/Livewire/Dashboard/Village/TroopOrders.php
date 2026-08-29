<?php

namespace App\Livewire\Dashboard\Village;

use App\Application\Accounts\Troops\QueueVillageTroopOrder;
use App\Application\Travian\TravianBuildingCatalog;
use App\Application\Travian\TravianTroopCatalog;
use App\Enums\VillageTroopOrderStatus;
use App\Jobs\RefreshVillageTroopSnapshotJob;
use App\Models\Village;
use App\Models\VillageTroopOrder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TroopOrders extends Component
{
    #[Locked]
    public int $villageId;

    public ?string $refreshRequestedAt = null;

    public string $message = '';

    public function mount(int $villageId): void
    {
        $this->villageId = $villageId;
        $village = $this->village();
        if ($village->troopSnapshot === null) {
            $this->refreshMilitaryState();
        }
    }

    public function refreshMilitaryState(): void
    {
        $village = $this->village();
        $this->refreshRequestedAt = now()->toIso8601String();
        $this->message = 'Checking Barracks, Stable, Academy and Smithy…';

        RefreshVillageTroopSnapshotJob::dispatch($village->account_id, $village->id);
    }

    public function checkRefresh(): void
    {
        if ($this->refreshRequestedAt === null) {
            return;
        }

        $village = $this->village();

        if ($village->troopSnapshot?->server_reported_at?->greaterThanOrEqualTo($this->refreshRequestedAt)) {
            $this->refreshRequestedAt = null;
            $this->message = 'Troop information updated.';
            $this->dispatch('troop-state-updated', villageId: $this->villageId)
                ->to(Row::class);

            return;
        }

        if (now()->diffInSeconds($this->refreshRequestedAt) > 120) {
            $this->refreshRequestedAt = null;
            $this->message = 'Military refresh did not finish. Check the account connection and try again.';
        }
    }

    public function pollTroopView(): void
    {
        $this->checkRefresh();
    }

    public function queueTraining(QueueVillageTroopOrder $queueVillageTroopOrder, int $unitId, int $quantity): void
    {
        $order = $queueVillageTroopOrder->training($this->village(), $unitId, $quantity);
        $this->message = 'Training scheduled. You can cancel it for one minute.';
        $this->dispatch('troop-state-updated', villageId: $this->villageId)
            ->to(Row::class);
    }

    public function queueSmithyUpgrade(QueueVillageTroopOrder $queueVillageTroopOrder, int $unitId, bool $useHeroResources = false): void
    {
        $order = $queueVillageTroopOrder->smithy($this->village(), $unitId, $useHeroResources);
        $this->message = 'Improvement scheduled. You can cancel it for one minute.';
        $this->dispatch('troop-state-updated', villageId: $this->villageId)
            ->to(Row::class);
    }

    public function cancelOrder(int $orderId, QueueVillageTroopOrder $queueVillageTroopOrder): void
    {
        $order = VillageTroopOrder::query()
            ->where('village_id', $this->villageId)
            ->findOrFail($orderId);

        $this->message = $queueVillageTroopOrder->cancel($order)
            ? 'Order cancelled.'
            : 'This order can no longer be cancelled.';
        $this->dispatch('troop-state-updated', villageId: $this->villageId)
            ->to(Row::class);
    }

    public function render(): View
    {
        $village = $this->village();

        return view('livewire.dashboard.village.troop-orders', [
            'units' => $this->units($village),
            'orders' => $village->troopOrders,
            'snapshotAt' => $village->troopSnapshot?->server_reported_at,
            'hasActiveOrders' => $village->troopOrders->contains(
                static fn (VillageTroopOrder $order): bool => in_array($order->status, [
                    VillageTroopOrderStatus::Scheduled,
                    VillageTroopOrderStatus::Claimed,
                    VillageTroopOrderStatus::WaitingResources,
                ], true),
            ),
            'smithyQueue' => $village->troopSnapshot?->smithy_queue ?? [],
        ]);
    }

    protected function village(): Village
    {
        return Village::query()
            ->with([
                'account',
                'runtimeState',
                'buildings',
                'troopSnapshot',
                'troopOrders' => fn ($query) => $query->latest('id')->limit(30),
            ])
            ->findOrFail($this->villageId);
    }

    /** @return list<array<string, mixed>> */
    protected function units(Village $village): array
    {
        $observedUnits = $village->troopSnapshot?->units ?? [];
        $smithyBuildingLevel = (int) ($village->buildings->firstWhere('building_gid', 13)?->current_level ?? 0);

        return collect(TravianTroopCatalog::definitionsForTribe($village->runtimeState?->tribe_id))
            ->map(function (array $definition) use ($observedUnits, $village, $smithyBuildingLevel): array {
                $observed = $observedUnits[(string) $definition['unit_id']] ?? [];
                $researchState = $observed['research_state'] ?? ($definition['initially_unlocked'] ? 'researched' : 'unknown');
                $trainable = (bool) data_get($observed, 'training.available', false);
                $requirements = collect(data_get($observed, 'research.requirements', $definition['research_requirements']))
                    ->map(static function (array $requirement): array {
                        $requiredLevel = (int) ($requirement['required_level'] ?? $requirement['level'] ?? 0);

                        return [
                            'name' => TravianBuildingCatalog::nameForGid((int) $requirement['gid'], 'en') ?? 'Building '.$requirement['gid'],
                            'required_level' => $requiredLevel,
                            'current_level' => isset($requirement['current_level']) ? (int) $requirement['current_level'] : null,
                            'met' => (bool) ($requirement['met'] ?? false),
                        ];
                    })
                    ->values()
                    ->all();
                [$statusLabel, $statusHelp, $statusTone] = $this->unitStatus($definition, $observed, $researchState, $trainable, $requirements);

                return [
                    ...$definition,
                    'research_state' => $researchState,
                    'trainable' => $trainable,
                    'max_trainable' => (int) data_get($observed, 'training.max_trainable', 0),
                    'smithy_level' => $village->troopSnapshot?->smithyLevelFor((int) $definition['unit_id']) ?? 0,
                    'smithy_actionable' => (bool) data_get($observed, 'smithy.actionable', false),
                    'smithy_resource_shortage' => (bool) data_get($observed, 'smithy.resource_shortage', false),
                    'smithy_cost' => data_get($observed, 'smithy.next_cost', []),
                    'smithy_duration_seconds' => (int) data_get($observed, 'smithy.duration_seconds', 0),
                    'smithy_message' => data_get($observed, 'smithy.server_message'),
                    'smithy_building_level' => $smithyBuildingLevel,
                    'training_building_name' => TravianBuildingCatalog::nameForGid((int) $definition['training_building_gid'], 'en'),
                    'requirements' => $requirements,
                    'status_label' => $statusLabel,
                    'status_help' => $statusHelp,
                    'status_tone' => $statusTone,
                ];
            })
            ->all();
    }

    /** @param array<string, mixed> $definition @param array<string, mixed> $observed @param list<array<string, mixed>> $requirements @return array{string, string, string} */
    protected function unitStatus(array $definition, array $observed, string $researchState, bool $trainable, array $requirements): array
    {
        if ($trainable) {
            return ['Ready to train', 'Ready in '.$this->trainingBuildingName($definition).'.', 'ready'];
        }

        if ($researchState === 'available') {
            return ['Research available', 'Research this unit in the Academy, then refresh the troop information.', 'attention'];
        }

        if ($researchState === 'in_progress') {
            return ['Research in progress', 'The Academy is currently researching this unit.', 'attention'];
        }

        if ($researchState === 'academy_busy') {
            return ['Academy busy', 'Another research is running. Recheck this unit after it finishes.', 'attention'];
        }

        if ($researchState === 'blocked_requirements') {
            $missing = collect($requirements)
                ->reject(static fn (array $requirement): bool => (bool) $requirement['met'])
                ->map(static fn (array $requirement): string => $requirement['name'].' Lv '.$requirement['required_level'])
                ->implode(', ');

            return ['Missing requirements', $missing !== '' ? 'Required: '.$missing.'.' : 'Open the Academy to see the missing requirements, then refresh.', 'blocked'];
        }

        if ($researchState === 'researched') {
            return ['Training building unavailable', 'The unit is researched, but '.$this->trainingBuildingName($definition).' is not ready. Refresh after checking the building.', 'blocked'];
        }

        $serverMessage = trim((string) data_get($observed, 'research.server_message', ''));

        return ['Not ready', $serverMessage !== '' ? $serverMessage : 'Open the Academy for details, then refresh the troop information.', 'muted'];
    }

    /** @param array<string, mixed> $definition */
    protected function trainingBuildingName(array $definition): string
    {
        return TravianBuildingCatalog::nameForGid((int) $definition['training_building_gid'], 'en') ?? 'the training building';
    }

    protected function firstTrainableUnitId(Village $village): ?int
    {
        $unit = collect($this->units($village))->firstWhere('trainable', true);

        return is_array($unit) ? (int) $unit['unit_id'] : null;
    }
}
