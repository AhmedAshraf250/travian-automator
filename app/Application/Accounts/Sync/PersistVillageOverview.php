<?php

namespace App\Application\Accounts\Sync;

use App\Application\Accounts\Sync\Data\ParsedConstructionQueueEntry;
use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedDorf2Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageSlot;
use App\Application\Accounts\Sync\Data\ParsedVillageSummary;
use App\Application\Travian\TravianBuildingCatalog;
use App\Models\Village;
use App\Models\VillageSetting;

/**
 * Persists one parsed village snapshot into the local database.
 */
class PersistVillageOverview
{
    /**
     * Persist one dorf1 + dorf2 snapshot onto an existing village model.
     */
    public function handle(
        Village $village,
        ParsedVillageSummary $summary,
        ParsedDorf1Overview $dorf1Overview,
        ParsedDorf2Overview $dorf2Overview,
    ): void {
        $isNewVillage = ! $village->exists;

        $village->fill([
            'name' => $summary->name,
            'x' => $summary->x,
            'y' => $summary->y,
            'population' => $summary->population ?? 0,
            'last_sync_at' => now(),
        ]);

        if ($isNewVillage) {
            $village->is_active = true;
        }

        $village->save();

        $village->settings()->firstOrCreate([], [
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);

        $village->resourceState()->updateOrCreate(
            [],
            [
                'wood' => $dorf1Overview->resourceState->wood,
                'clay' => $dorf1Overview->resourceState->clay,
                'iron' => $dorf1Overview->resourceState->iron,
                'crop' => $dorf1Overview->resourceState->crop,
                'wood_production' => $dorf1Overview->resourceState->woodProduction,
                'clay_production' => $dorf1Overview->resourceState->clayProduction,
                'iron_production' => $dorf1Overview->resourceState->ironProduction,
                'crop_production' => $dorf1Overview->resourceState->cropProduction,
                'warehouse_capacity' => $dorf1Overview->resourceState->warehouseCapacity,
                'granary_capacity' => $dorf1Overview->resourceState->granaryCapacity,
                'simulated_at' => now(),
                'server_reported_at' => now(),
            ],
        );

        $village->runtimeState()->updateOrCreate(
            [],
            [
                'tribe_id' => $dorf1Overview->runtimeState->tribeId,
                'troop_slots' => $dorf1Overview->runtimeState->troopSlots,
                'incoming_attack_count' => $dorf1Overview->runtimeState->incomingAttackCount,
                'incoming_reinforcement_count' => $dorf1Overview->runtimeState->incomingReinforcementCount,
                'outgoing_movement_count' => $dorf1Overview->runtimeState->outgoingMovementCount,
                'movement_entries' => array_map(
                    static fn ($entry): array => [
                        'kind' => $entry->kind,
                        'label' => $entry->label,
                        'count' => $entry->count,
                        'remaining_seconds' => $entry->remainingSeconds,
                        'remaining_label' => $entry->remainingLabel,
                    ],
                    $dorf1Overview->runtimeState->movementEntries,
                ),
                'construction_entries' => array_map(
                    static fn ($entry): array => [
                        'building_name' => $entry->buildingName,
                        'target_level' => $entry->targetLevel,
                        'remaining_seconds' => $entry->remainingSeconds,
                        'remaining_label' => $entry->remainingLabel,
                        'finish_label' => $entry->finishLabel,
                    ],
                    $dorf1Overview->runtimeState->constructionEntries,
                ),
                'hero_status' => $dorf1Overview->runtimeState->heroStatus,
                'hero_remaining_seconds' => $dorf1Overview->runtimeState->heroRemainingSeconds,
                'server_reported_at' => now(),
            ],
        );

        $this->syncVillageSlots($village, array_merge($dorf1Overview->fieldSlots, $dorf2Overview->buildingSlots));
        $this->syncConstructionQueue($village, $dorf1Overview->constructionQueue);
        $this->hydrateKnownTargetGids($village);
    }

    /**
     * Persist the current field and building slots for a village.
     *
     * @param  list<ParsedVillageSlot>  $slots
     */
    protected function syncVillageSlots(Village $village, array $slots): void
    {
        $slotIds = [];

        foreach ($slots as $slot) {
            $slotIds[] = $slot->slotId;

            $village->buildings()->updateOrCreate(
                ['slot_id' => $slot->slotId],
                [
                    'building_gid' => $slot->buildingGid,
                    'building_type' => $slot->isEmpty ? null : $slot->buildingName,
                    'current_level' => $slot->currentLevel,
                    'is_under_construction' => false,
                    'finish_at' => null,
                ],
            );
        }

        if ($slotIds !== []) {
            $village->buildings()
                ->whereBetween('slot_id', [1, 40])
                ->whereNotIn('slot_id', $slotIds)
                ->delete();
        }
    }

    /**
     * Persist the current construction queue snapshot using synthetic slot ids.
     *
     * @param  list<ParsedConstructionQueueEntry>  $constructionQueue
     */
    protected function syncConstructionQueue(Village $village, array $constructionQueue): void
    {
        $village->buildings()
            ->where('slot_id', '>=', 200)
            ->delete();

        foreach ($constructionQueue as $queueIndex => $constructionQueueEntry) {
            $village->buildings()->updateOrCreate(
                ['slot_id' => 200 + $queueIndex],
                [
                    'building_gid' => TravianBuildingCatalog::gidForName($constructionQueueEntry->buildingName) ?? 0,
                    'building_type' => $constructionQueueEntry->buildingName,
                    'current_level' => $constructionQueueEntry->targetLevel,
                    'is_under_construction' => true,
                    'finish_at' => now()->addSeconds($constructionQueueEntry->remainingSeconds),
                ],
            );
        }
    }

    /**
     * Backfill gid values for any existing village building targets that still only store names.
     */
    protected function hydrateKnownTargetGids(Village $village): void
    {
        foreach ($village->buildingTargets()->where('building_gid', 0)->get() as $target) {
            $resolvedGid = TravianBuildingCatalog::gidForName($target->building_type);

            if ($resolvedGid === null) {
                continue;
            }

            $target->forceFill([
                'building_gid' => $resolvedGid,
            ])->save();
        }
    }
}
