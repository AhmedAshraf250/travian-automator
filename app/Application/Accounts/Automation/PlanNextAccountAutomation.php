<?php

namespace App\Application\Accounts\Automation;

use App\Application\Travian\TravianBuildingCatalog;
use App\Models\Account;
use App\Models\Village;
use App\Models\VillageSetting;
use Carbon\CarbonImmutable;

class PlanNextAccountAutomation
{
    /**
     * Choose the next time an account should be considered by the dispatcher.
     *
     * Missing snapshots are due immediately. Active timers schedule the account
     * shortly after the next known completion. Quiet accounts fall back to a
     * slower idle interval.
     */
    public function handle(Account $account): CarbonImmutable
    {
        $account->loadMissing('villages.settings', 'villages.runtimeState');

        $now = now()->toImmutable();
        $idleAt = $now->addMinutes(max(1, (int) config('travian.automation.idle_minutes', 10)));
        $timerGraceSeconds = max(5, (int) config('travian.automation.timer_grace_seconds', 45));
        $nextTimerSeconds = null;

        foreach ($account->villages->where('is_active', true) as $village) {
            if ($this->snapshotIsMissing($village)) {
                return $now;
            }

            if ($this->hasOpenAutomationLane($village)) {
                return $now;
            }

            foreach ($this->remainingTimers($village) as $remainingSeconds) {
                if ($remainingSeconds <= 0) {
                    return $now->addSeconds($timerGraceSeconds);
                }

                $nextTimerSeconds = $nextTimerSeconds === null
                    ? $remainingSeconds
                    : min($nextTimerSeconds, $remainingSeconds);
            }
        }

        if ($nextTimerSeconds !== null) {
            return $now->addSeconds($nextTimerSeconds + $timerGraceSeconds);
        }

        return $idleAt;
    }

    /**
     * Determine whether the village lacks enough local data for planning.
     */
    protected function snapshotIsMissing(Village $village): bool
    {
        return $village->last_sync_at === null || $village->runtimeState === null;
    }

    /**
     * Decide whether a village still has an automation lane worth probing soon.
     */
    protected function hasOpenAutomationLane(Village $village): bool
    {
        $settings = $village->settings;
        $runtimeState = $village->runtimeState;

        if (! $settings instanceof VillageSetting || $runtimeState === null) {
            return false;
        }

        if ($settings->pause_fields && $settings->pause_buildings) {
            return false;
        }

        $queueAvailability = $this->resolveQueueAvailability(
            is_array($runtimeState->construction_entries) ? $runtimeState->construction_entries : [],
            $runtimeState->tribe_id !== null ? (int) $runtimeState->tribe_id : null,
        );

        return (! $settings->pause_fields && $queueAvailability['field'])
            || (! $settings->pause_buildings && $queueAvailability['building']);
    }

    /**
     * Mirror the construction executor's queue availability rules for planning.
     *
     * @param  list<array<string, mixed>>  $constructionEntries
     * @return array{field: bool, building: bool}
     */
    protected function resolveQueueAvailability(array $constructionEntries, ?int $tribeId): array
    {
        if (! TravianBuildingCatalog::isRomanTribe($tribeId)) {
            $queueIsOpen = $constructionEntries === [];

            return [
                'field' => $queueIsOpen,
                'building' => $queueIsOpen,
            ];
        }

        $availability = [
            'field' => true,
            'building' => true,
        ];

        foreach ($constructionEntries as $constructionEntry) {
            $queueKind = TravianBuildingCatalog::queueKindForName($constructionEntry['building_name'] ?? null);

            if ($queueKind === 'field') {
                $availability['field'] = false;

                continue;
            }

            if ($queueKind === 'building') {
                $availability['building'] = false;

                continue;
            }

            return [
                'field' => false,
                'building' => false,
            ];
        }

        return $availability;
    }

    /**
     * Return known countdowns that can influence the next useful check.
     *
     * @return list<int>
     */
    protected function remainingTimers(Village $village): array
    {
        $runtimeState = $village->runtimeState;

        if ($runtimeState === null) {
            return [];
        }

        $timers = [];

        $elapsedSeconds = $runtimeState->server_reported_at !== null
            ? max(0, now()->getTimestamp() - $runtimeState->server_reported_at->getTimestamp())
            : 0;

        foreach ($runtimeState->construction_entries ?? [] as $entry) {
            if (isset($entry['remaining_seconds'])) {
                $timers[] = (int) $entry['remaining_seconds'] - $elapsedSeconds;
            }
        }

        foreach ($runtimeState->movement_entries ?? [] as $entry) {
            if (isset($entry['remaining_seconds'])) {
                $timers[] = (int) $entry['remaining_seconds'] - $elapsedSeconds;
            }
        }

        if ($runtimeState->hero_remaining_seconds !== null && $runtimeState->hero_status !== 'home') {
            $timers[] = (int) $runtimeState->hero_remaining_seconds - $elapsedSeconds;
        }

        return $timers;
    }
}
