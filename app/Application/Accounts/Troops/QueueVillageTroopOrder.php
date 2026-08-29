<?php

namespace App\Application\Accounts\Troops;

use App\Application\Travian\TravianTroopCatalog;
use App\Enums\VillageTroopOrderStatus;
use App\Jobs\ExecuteVillageTroopOrderJob;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageTroopOrder;
use Illuminate\Validation\ValidationException;

class QueueVillageTroopOrder
{
    public function training(Village $village, int $unitId, int $quantity): VillageTroopOrder
    {
        if (! SystemSetting::automationEnabled() || ! $village->is_active || ! $village->account?->is_active || $village->account?->is_archived) {
            throw ValidationException::withMessages(['quantity' => 'Resume the program, account and village before queuing a troop order.']);
        }

        if ($quantity < 1 || $quantity > 9999) {
            throw ValidationException::withMessages(['quantity' => 'Choose a quantity between 1 and 9999.']);
        }

        $definition = TravianTroopCatalog::definition($unitId);
        $observed = $village->troopSnapshot?->units[(string) $unitId] ?? null;
        $tribeId = $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null;

        if (! is_array($definition) || (int) $definition['tribe_id'] !== $tribeId) {
            throw ValidationException::withMessages(['unitId' => 'This unit does not belong to the village tribe.']);
        }

        if (! $definition['training_supported']) {
            throw ValidationException::withMessages(['unitId' => 'Workshop training is not supported yet.']);
        }

        if (! is_array($observed) || ($observed['research_state'] ?? null) !== 'researched' || ! data_get($observed, 'training.available', false)) {
            throw ValidationException::withMessages(['unitId' => 'This unit is not available for training. Refresh the troop information first.']);
        }

        $executeAfter = now()->addMinute();
        $order = $village->troopOrders()->create([
            'unit_id' => $unitId,
            'order_type' => VillageTroopOrder::TypeTraining,
            'requested_quantity' => $quantity,
            'status' => VillageTroopOrderStatus::Scheduled,
            'execute_after' => $executeAfter,
        ]);

        ExecuteVillageTroopOrderJob::dispatch($village->account_id, $order->id)->delay($executeAfter);

        return $order;
    }

    public function smithy(Village $village, int $unitId, bool $useHeroResources = false): VillageTroopOrder
    {
        $this->ensureVillageCanQueue($village, 'unitId');

        $definition = TravianTroopCatalog::definition($unitId);
        $observed = $village->troopSnapshot?->units[(string) $unitId] ?? null;
        $tribeId = $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null;

        if (! is_array($definition) || (int) $definition['tribe_id'] !== $tribeId) {
            throw ValidationException::withMessages(['unitId' => 'This unit does not belong to the village tribe.']);
        }

        if (! is_array($observed) || ($observed['research_state'] ?? null) !== 'researched') {
            throw ValidationException::withMessages(['unitId' => 'Only researched units can be improved in the Smithy.']);
        }

        $currentLevel = $village->troopSnapshot?->smithyLevelFor($unitId) ?? 0;

        if ($currentLevel >= 20) {
            throw ValidationException::withMessages(['unitId' => 'This unit already reached Smithy level 20.']);
        }

        $smithyActionable = (bool) data_get($observed, 'smithy.actionable', false);
        $verifiedResourceShortage = (bool) data_get($observed, 'smithy.resource_shortage', false);

        if (! $smithyActionable && (! $useHeroResources || ! $verifiedResourceShortage)) {
            $message = trim((string) data_get($observed, 'smithy.server_message', ''));

            throw ValidationException::withMessages([
                'unitId' => $message !== '' ? $message : 'The next Smithy level is not currently available. Refresh the military state for current details.',
            ]);
        }

        if (($village->troopSnapshot?->smithy_queue ?? []) !== []) {
            throw ValidationException::withMessages(['unitId' => 'The Smithy is already improving another unit.']);
        }

        if ($village->troopOrders()
            ->where('order_type', VillageTroopOrder::TypeSmithy)
            ->whereIn('status', [VillageTroopOrderStatus::Scheduled->value, VillageTroopOrderStatus::Claimed->value])
            ->exists()) {
            throw ValidationException::withMessages(['unitId' => 'Another Smithy improvement is already scheduled.']);
        }

        $executeAfter = now()->addMinute();
        $order = $village->troopOrders()->create([
            'unit_id' => $unitId,
            'order_type' => VillageTroopOrder::TypeSmithy,
            'requested_quantity' => 1,
            'target_level' => $currentLevel + 1,
            'use_hero_resources' => $useHeroResources,
            'status' => VillageTroopOrderStatus::Scheduled,
            'execute_after' => $executeAfter,
        ]);

        ExecuteVillageTroopOrderJob::dispatch($village->account_id, $order->id)->delay($executeAfter);

        return $order;
    }

    public function cancel(VillageTroopOrder $order): bool
    {
        return VillageTroopOrder::query()
            ->whereKey($order->id)
            ->whereIn('status', [
                VillageTroopOrderStatus::Scheduled->value,
                VillageTroopOrderStatus::WaitingResources->value,
            ])
            ->update([
                'status' => VillageTroopOrderStatus::Cancelled->value,
                'cancelled_at' => now(),
                'result_message' => 'Cancelled before submission to Travian.',
            ]) === 1;
    }

    protected function ensureVillageCanQueue(Village $village, string $errorKey): void
    {
        if (! SystemSetting::automationEnabled() || ! $village->is_active || ! $village->account?->is_active || $village->account?->is_archived) {
            throw ValidationException::withMessages([$errorKey => 'Resume the program, account and village before queuing a troop order.']);
        }
    }
}
