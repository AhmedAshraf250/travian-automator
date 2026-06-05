<?php

namespace App\Application\Accounts\Automation;

use App\Application\Travian\TravianBuildingCatalog;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageSetting;
use Carbon\CarbonImmutable;
use Throwable;

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
        $account->loadMissing('settings', 'heroState', 'villages.settings', 'villages.runtimeState');

        $now = now()->toImmutable();
        $idleAt = $now->addMinutes(max(1, (int) config('travian.automation.idle_minutes', 10)));
        $timerGraceSeconds = max(5, (int) config('travian.automation.timer_grace_seconds', 45));
        $nextTimerSeconds = null;

        foreach ($account->villages->where('is_active', true) as $village) {
            if ($this->snapshotIsMissing($village)) {
                return $now;
            }

            if ($this->hasOpenAutomationLane($village)) {
                $shortageTimers = $this->remainingResourceShortageTimers($village);

                if ($shortageTimers === []) {
                    return $now;
                }

                foreach ($shortageTimers as $remainingSeconds) {
                    if ($remainingSeconds <= 0) {
                        return $now->addSeconds($timerGraceSeconds);
                    }

                    $nextTimerSeconds = $nextTimerSeconds === null
                        ? $remainingSeconds
                        : min($nextTimerSeconds, $remainingSeconds);
                }
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

        foreach ($this->remainingHeroTimers($account) as $remainingSeconds) {
            if ($remainingSeconds <= 0) {
                return $now->addSeconds($timerGraceSeconds);
            }

            $nextTimerSeconds = $nextTimerSeconds === null
                ? $remainingSeconds
                : min($nextTimerSeconds, $remainingSeconds);
        }

        if ($this->hasDueHeroAutomation($account)) {
            return $now;
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

    /**
     * Return saved resource shortage countdowns from the latest automation pass.
     *
     * @return list<int>
     */
    protected function remainingResourceShortageTimers(Village $village): array
    {
        $runtimeState = $village->runtimeState;

        if ($runtimeState === null || ! is_array($runtimeState->construction_resource_shortages)) {
            return [];
        }

        $timers = [];

        foreach ($runtimeState->construction_resource_shortages as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $readyAt = $entry['resource_ready_at'] ?? null;

            if (is_string($readyAt) && $readyAt !== '') {
                try {
                    $timers[] = CarbonImmutable::parse($readyAt)->getTimestamp() - now()->getTimestamp();

                    continue;
                } catch (Throwable) {
                }
            }

            if (! isset($entry['resource_ready_seconds'])) {
                continue;
            }

            $elapsedSeconds = 0;
            $recordedAt = $entry['recorded_at'] ?? null;

            if (is_string($recordedAt) && $recordedAt !== '') {
                try {
                    $elapsedSeconds = max(0, now()->getTimestamp() - CarbonImmutable::parse($recordedAt)->getTimestamp());
                } catch (Throwable) {
                    $elapsedSeconds = 0;
                }
            }

            $timers[] = (int) $entry['resource_ready_seconds'] - $elapsedSeconds;
        }

        return $timers;
    }

    /**
     * Return account-level hero countdowns saved by hero automation.
     *
     * @return list<int>
     */
    protected function remainingHeroTimers(Account $account): array
    {
        $heroState = $account->heroState;

        if ($heroState === null || $heroState->hero_remaining_seconds === null) {
            return [];
        }

        if (! in_array($heroState->status, ['adventure', 'returning', 'regenerating'], true)) {
            return [];
        }

        $elapsedSeconds = $heroState->seen_at !== null
            ? max(0, now()->getTimestamp() - $heroState->seen_at->getTimestamp())
            : 0;

        return [(int) $heroState->hero_remaining_seconds - $elapsedSeconds];
    }

    /**
     * Decide whether hero automation has a known immediate action.
     */
    protected function hasDueHeroAutomation(Account $account): bool
    {
        $settings = $this->resolveHeroSettings($account);

        if (
            ! $settings['adventures_enabled']
            && ! $settings['revive_enabled']
            && ! $settings['attribute_upgrade_enabled']
        ) {
            return false;
        }

        $heroState = $account->heroState;

        if ($heroState === null) {
            return true;
        }

        if ($settings['revive_enabled'] && $heroState->status === 'dead') {
            return true;
        }

        if ($settings['attribute_upgrade_enabled'] && $heroState->has_unspent_attribute_points) {
            return true;
        }

        $heroStateSource = $heroState->payload['source'] ?? null;

        return $settings['adventures_enabled']
            && $heroState->status === 'home'
            && ((int) $heroState->adventures_available_count > 0 || $heroStateSource === 'data_for_hud')
            && (float) ($heroState->health_percent ?? 0) >= $settings['min_health'];
    }

    /**
     * Resolve the effective hero defaults for planning.
     *
     * @return array{adventures_enabled: bool, min_health: int, revive_enabled: bool, attribute_upgrade_enabled: bool}
     */
    protected function resolveHeroSettings(Account $account): array
    {
        $settings = $account->settings;

        if (! $settings instanceof AccountSetting || (bool) $settings->hero_use_global_settings) {
            $defaults = SystemSetting::heroDefaults();

            return [
                'adventures_enabled' => $defaults['adventures_enabled'],
                'min_health' => $defaults['min_health'],
                'revive_enabled' => $defaults['revive_enabled'],
                'attribute_upgrade_enabled' => $defaults['attribute_upgrade_enabled'],
            ];
        }

        return [
            'adventures_enabled' => (bool) $settings->hero_adventures_enabled,
            'min_health' => max(0, min(100, (int) ($settings->hero_min_health ?? 40))),
            'revive_enabled' => (bool) $settings->hero_revive_enabled,
            'attribute_upgrade_enabled' => (bool) $settings->hero_attribute_upgrade_enabled,
        ];
    }
}
