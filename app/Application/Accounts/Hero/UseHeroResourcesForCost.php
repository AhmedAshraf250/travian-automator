<?php

namespace App\Application\Accounts\Hero;

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Enums\ActivityLogStatus;
use App\Models\Account;
use App\Models\Village;
use App\Models\VillageResourceState;
use App\Models\VillageSetting;

class UseHeroResourcesForCost extends UseHeroResourcesForConstruction
{
    /**
     * @param  array{wood?: int, clay?: int, iron?: int, crop?: int}  $requiredResources
     * @param  array<string, mixed>  $contextPayload
     */
    public function handleCost(
        Account $account,
        Village $village,
        AccountSession $session,
        array $requiredResources,
        string $referer,
        string $purpose,
        bool $manualOverride = false,
        array $contextPayload = [],
    ): bool {
        $village = $village->fresh(['resourceState', 'settings']);

        if (! $village instanceof Village || ! $village->resourceState instanceof VillageResourceState) {
            return false;
        }

        if (! $manualOverride && $village->settings instanceof VillageSetting && ! (bool) $village->settings->hero_resources_enabled) {
            return false;
        }

        $absoluteReferer = $this->absoluteUri($referer, $account);
        $inventorySnapshot = $this->fetchHeroInventory($session, $absoluteReferer);

        if ($inventorySnapshot === null) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Failed, "Hero resource inventory could not be read for {$purpose}.", $contextPayload);

            return false;
        }

        $liveResources = $inventorySnapshot['village_resources'];

        if (! $this->storageCanHoldRequiredResources($requiredResources, $liveResources)) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Pending, "Hero resources skipped because village storage is too small for {$purpose}.", [
                ...$contextPayload,
                'required_resources' => $requiredResources,
                'village_resources' => $liveResources,
            ]);

            return false;
        }

        $shortages = $this->resourceShortages($village, $requiredResources, $liveResources);

        if ($this->resourceListIsEmpty($shortages)) {
            return true;
        }

        $resourceItems = $this->heroResourceItems($inventorySnapshot['items']);
        $uncoveredResources = $this->uncoveredResources($shortages, $resourceItems);

        if ($uncoveredResources !== []) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Pending, "Hero resources could not fully cover the {$purpose} shortage.", [
                ...$contextPayload,
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

        $preparedUses = $this->previewHeroResourceUses($session, $absoluteReferer, $shortages, $resourceItems, $travianVillageId);

        if ($preparedUses === null) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Failed, "Hero resource preview was rejected for {$purpose}.", [
                ...$contextPayload,
                'needed_resources' => $shortages,
            ]);

            return false;
        }

        [$usedResources, $allConfirmed] = $this->confirmHeroResourceUses($session, $absoluteReferer, $preparedUses);

        if (! $this->resourceListIsEmpty($usedResources)) {
            $this->addLocalVillageResources($village, $usedResources);
        }

        if (! $allConfirmed) {
            $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Failed, "Hero resource confirmation was rejected for {$purpose}.", [
                ...$contextPayload,
                'needed_resources' => $shortages,
                'used_resources' => $usedResources,
            ]);

            return false;
        }

        $this->logHeroResourceActivity($account, $village, ActivityLogStatus::Done, "Hero resources moved to the village for {$purpose}.", [
            ...$contextPayload,
            'resources' => $usedResources,
            'effective_uri' => $inventorySnapshot['effective_uri'],
            'status_code' => $inventorySnapshot['status_code'],
        ]);

        return true;
    }
}
