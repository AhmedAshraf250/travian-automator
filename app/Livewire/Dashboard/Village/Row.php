<?php

namespace App\Livewire\Dashboard\Village;

use App\Application\Accounts\Automation\QueueAccountWork;
use App\Application\Accounts\Troops\QueueVillageTroopOrder;
use App\Application\Travian\TravianTroopCatalog;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Enums\VillageTroopOrderStatus;
use App\Livewire\Dashboard\Concerns\ManagesAutomationControls;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageTroopOrder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Row extends Component
{
    use ManagesAutomationControls {
        toggleVillage as protected toggleVillageUsingDashboardControls;
        toggleVillageFieldsAutomation as protected toggleVillageFieldsAutomationUsingDashboardControls;
        toggleVillageBuildingsAutomation as protected toggleVillageBuildingsAutomationUsingDashboardControls;
        toggleVillageHeroResources as protected toggleVillageHeroResourcesUsingDashboardControls;
        toggleVillageFieldSlotAutomation as protected toggleVillageFieldSlotAutomationUsingDashboardControls;
        toggleVillageBuildingSlotAutomation as protected toggleVillageBuildingSlotAutomationUsingDashboardControls;
        toggleVillageCelebrationAutomation as protected toggleVillageCelebrationAutomationUsingDashboardControls;
        toggleVillageCelebrationHeroResources as protected toggleVillageCelebrationHeroResourcesUsingDashboardControls;
        toggleVillageSchedulePin as protected toggleVillageSchedulePinUsingDashboardControls;
        toggleVillageScheduleHold as protected toggleVillageScheduleHoldUsingDashboardControls;
        requestVillageSync as protected requestVillageSyncUsingDashboardControls;
    }

    #[Locked]
    public int $villageId;

    #[Reactive]
    public array $globalFieldPriority = [];

    #[Reactive]
    public int $globalFieldLevelCap = 10;

    #[Reactive]
    public bool $globalPrioritizeCropFieldsWhenNegative = true;

    #[Reactive]
    public string $dashboardRevision = '';

    public function toggleVillage(int $villageId): void
    {
        $this->toggleVillageUsingDashboardControls($villageId);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageFieldsAutomation(int $villageId): void
    {
        $this->toggleVillageFieldsAutomationUsingDashboardControls($villageId);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageBuildingsAutomation(int $villageId): void
    {
        $this->toggleVillageBuildingsAutomationUsingDashboardControls($villageId);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageHeroResources(int $villageId): void
    {
        $this->toggleVillageHeroResourcesUsingDashboardControls($villageId);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageFieldSlotAutomation(int $villageId, int $slotId): void
    {
        $this->toggleVillageFieldSlotAutomationUsingDashboardControls($villageId, $slotId);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageBuildingSlotAutomation(int $villageId, int $slotId): void
    {
        $this->toggleVillageBuildingSlotAutomationUsingDashboardControls($villageId, $slotId);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageCelebrationAutomation(int $villageId): void
    {
        $this->toggleVillageCelebrationAutomationUsingDashboardControls($villageId);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageCelebrationHeroResources(int $villageId): void
    {
        $this->toggleVillageCelebrationHeroResourcesUsingDashboardControls($villageId);
        $this->dispatch('dashboard-row-updated');
    }

    public function queueQuickTroopOrder(QueueVillageTroopOrder $queueVillageTroopOrder, int $unitId, int $quantity): void
    {
        $order = $queueVillageTroopOrder->training(
            $this->village(),
            $unitId,
            $quantity,
        );
        session()->flash('dashboard-banner', "Troop order #{$order->id} queued with a one-minute cancellation window.");
        $this->dispatch('dashboard-row-updated');
        $this->dispatch('troop-state-updated', villageId: $this->villageId);
    }

    public function cancelQuickTroopOrder(int $orderId, QueueVillageTroopOrder $queueVillageTroopOrder): void
    {
        $order = VillageTroopOrder::query()
            ->where('village_id', $this->villageId)
            ->findOrFail($orderId);
        $cancelled = $queueVillageTroopOrder->cancel($order);
        session()->flash('dashboard-banner', $cancelled
            ? "Troop order #{$order->id} cancelled."
            : "Troop order #{$order->id} can no longer be cancelled locally.");
        $this->dispatch('dashboard-row-updated');
        $this->dispatch('troop-state-updated', villageId: $this->villageId);
    }

    #[On('troop-state-updated')]
    public function refreshTroopState(int $villageId): void
    {
        if ($villageId !== $this->villageId) {
            $this->skipRender();
        }
    }

    public function toggleVillageSchedulePin(int $villageId, string $scheduleKey): void
    {
        $this->toggleVillageSchedulePinUsingDashboardControls($villageId, $scheduleKey);
        $this->dispatch('dashboard-row-updated');
    }

    public function toggleVillageScheduleHold(int $villageId, string $scheduleKey): void
    {
        $this->toggleVillageScheduleHoldUsingDashboardControls($villageId, $scheduleKey);
        $this->dispatch('dashboard-row-updated');
    }

    public function requestVillageSync(int $villageId): void
    {
        $this->requestVillageSyncUsingDashboardControls($villageId);
        $this->dispatch('dashboard-row-updated');
    }

    public function queueVillageTimerSync(int $villageId): void
    {
        if (! SystemSetting::automationEnabled()) {
            $this->skipRender();

            return;
        }

        $village = Village::query()->with('account')->findOrFail($villageId);

        if (! $this->villageCanQueueSync($village)) {
            $this->skipRender();

            return;
        }

        if ($this->recentVillageSyncAlreadyQueued($village)) {
            $this->skipRender();

            return;
        }

        $this->queueVillageSync($village, 'Village timer elapsed; sync queued automatically.', true);

        $this->skipRender();
    }

    public function render(): View
    {
        $village = $this->village();

        return view('livewire.dashboard.village.row', [
            'village' => $village,
            'automationEnabled' => SystemSetting::automationEnabled(),
            'globalFieldPriority' => $this->globalFieldPriority,
            'globalFieldLevelCap' => $this->globalFieldLevelCap,
            'globalPrioritizeCropFieldsWhenNegative' => $this->globalPrioritizeCropFieldsWhenNegative,
            'quickTroopUnits' => $this->quickTroopUnits($village),
            'quickTroopOrders' => $village->troopOrders
                ->whereIn('status', [VillageTroopOrderStatus::Scheduled, VillageTroopOrderStatus::WaitingResources])
                ->values(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    protected function quickTroopUnits(Village $village): array
    {
        $observedUnits = $village->troopSnapshot?->units ?? [];
        $tribeId = $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null;

        return collect(TravianTroopCatalog::definitionsForTribe($tribeId))
            ->filter(static fn (array $definition): bool => (bool) $definition['training_supported'])
            ->filter(static fn (array $definition): bool => (bool) data_get(
                $observedUnits,
                $definition['unit_id'].'.training.available',
                false,
            ))
            ->map(static fn (array $definition): array => [
                'unit_id' => (int) $definition['unit_id'],
                'english' => (string) $definition['english'],
                'arabic' => (string) $definition['arabic'],
                'icon_path' => (string) $definition['icon_path'],
                'smithy_level' => $village->troopSnapshot?->smithyLevelFor((int) $definition['unit_id']) ?? 0,
                'max_trainable' => (int) data_get($observedUnits, $definition['unit_id'].'.training.max_trainable', 0),
            ])
            ->values()
            ->all();
    }

    protected function village(): Village
    {
        return Village::query()
            ->with([
                'account',
                'settings',
                'resourceState',
                'runtimeState',
                'buildings' => fn ($query) => $query->orderBy('slot_id'),
                'buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
                'troopSnapshot',
                'troopOrders' => fn ($query) => $query->latest('id')->limit(5),
            ])
            ->findOrFail($this->villageId);
    }

    protected function queueVillageSync(Village $village, string $message, bool $useReloadAuto = false): bool
    {
        return app(QueueAccountWork::class)->queueVillageSync($village, $message, $useReloadAuto);
    }

    /** Reactive revisions are invalidated by the dashboard parent. */
    protected function invalidateDashboardRevision(): void {}

    protected function villageCanQueueSync(Village $village): bool
    {
        $account = $village->account;

        return $account instanceof Account
            && $account->is_active
            && ! $account->is_archived
            && $village->is_active;
    }

    protected function recentVillageSyncAlreadyQueued(Village $village): bool
    {
        return ActivityLog::query()
            ->where('account_id', $village->account_id)
            ->where('village_id', $village->id)
            ->where('activity_type', ActivityType::Sync->value)
            ->whereIn('status', [ActivityLogStatus::Pending->value, ActivityLogStatus::Running->value])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();
    }

    protected function logManualActivity(Account $account, ?Village $village, string $message): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village?->id,
            'activity_type' => ActivityType::Manual,
            'status' => ActivityLogStatus::Done,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
