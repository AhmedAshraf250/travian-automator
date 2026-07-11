<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Enums\VillageCelebrationType;
use App\Jobs\RunTravianAutomationJob;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageSetting;

trait ManagesAutomationControls
{
    /**
     * Activate an account from the dashboard.
     */
    public function activateAccount(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        $account->forceFill([
            'is_active' => true,
            'status' => AccountStatus::Active,
        ])->save();

        $this->logManualActivity($account, null, 'Account activated from dashboard.');
    }

    /**
     * Pause an account from the dashboard.
     */
    public function pauseAccount(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        $account->forceFill([
            'is_active' => false,
            'status' => AccountStatus::Paused,
        ])->save();

        $this->logManualActivity($account, null, 'Account paused from dashboard.');
    }

    /**
     * Queue a manual sync marker for an account.
     */
    public function requestAccountSync(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        if (! SystemSetting::automationEnabled()) {
            session()->flash('dashboard-banner', 'Program automation is paused. Resume it before requesting an account update.');

            return;
        }

        if (! $account->is_active || $account->is_archived) {
            session()->flash('dashboard-banner', "Account {$account->username} is paused. Activate it before requesting an update.");

            return;
        }

        $account->forceFill([
            'connection_retry_after' => null,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Pending,
            'message' => 'Sync requested and queued from dashboard.',
            'scheduled_at' => now(),
        ]);

        SyncTravianAccountJob::dispatch($account->id, null, true);

        $this->dashboardRevision = '';

        session()->flash('dashboard-banner', "Account {$account->username} was queued for background sync.");
    }

    /**
     * Toggle village active state.
     */
    public function toggleVillage(int $villageId): void
    {
        $village = Village::query()->findOrFail($villageId);

        $village->forceFill([
            'is_active' => ! $village->is_active,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            $village->is_active ? 'Village activated from dashboard.' : 'Village paused from dashboard.',
        );
    }

    /**
     * Toggle automatic field upgrades for one village.
     */
    public function toggleVillageFieldsAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isPaused = (bool) $settings->pause_fields;

        $settings->forceFill([
            'pause_fields' => ! $isPaused,
        ])->save();

        $message = $isPaused
            ? 'Village field automation enabled from dashboard.'
            : 'Village field automation paused from dashboard.';

        $this->logManualActivity($village->account, $village, $message);
        session()->flash('dashboard-banner', "{$village->name}: ".($isPaused ? 'field upgrades are now ON.' : 'field upgrades are now OFF.'));
    }

    /**
     * Toggle automatic building upgrades for one village.
     */
    public function toggleVillageBuildingsAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isPaused = (bool) $settings->pause_buildings;

        $settings->forceFill([
            'pause_buildings' => ! $isPaused,
        ])->save();

        $message = $isPaused
            ? 'Village building automation enabled from dashboard.'
            : 'Village building automation paused from dashboard.';

        $this->logManualActivity($village->account, $village, $message);
        session()->flash('dashboard-banner', "{$village->name}: ".($isPaused ? 'building upgrades are now ON.' : 'building upgrades are now OFF.'));
    }

    /**
     * Toggle hero resource usage for construction shortages in one village.
     */
    public function toggleVillageHeroResources(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isEnabled = ! (bool) $settings->hero_resources_enabled;

        $settings->forceFill([
            'hero_resources_enabled' => $isEnabled,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            'Village hero resource usage '.($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );

        session()->flash('dashboard-banner', "{$village->name}: hero resources are now ".($isEnabled ? 'ON.' : 'OFF.'));
    }

    /**
     * Toggle one field slot inside the village field automation list.
     */
    public function toggleVillageFieldSlotAutomation(int $villageId, int $slotId): void
    {
        if ($slotId < 1 || $slotId > 18) {
            return;
        }

        $village = Village::query()->with('account')->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $slot = $village->buildings()
            ->where('slot_id', $slotId)
            ->whereBetween('building_gid', [1, 4])
            ->firstOrFail();
        $isEnabled = ! (bool) $slot->automation_enabled;

        $slot->forceFill([
            'automation_enabled' => $isEnabled,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            "Field slot {$slotId} automation ".($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Toggle one existing building slot and mirror the state to its layout target.
     */
    public function toggleVillageBuildingSlotAutomation(int $villageId, int $slotId): void
    {
        if ($slotId < 19 || $slotId > 40) {
            return;
        }

        $village = Village::query()->with('account')->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $slot = $village->buildings()
            ->where('slot_id', $slotId)
            ->where('building_gid', '>', 0)
            ->firstOrFail();
        $isEnabled = ! (bool) $slot->automation_enabled;

        $slot->forceFill([
            'automation_enabled' => $isEnabled,
        ])->save();

        $village->buildingTargets()
            ->where('slot_id', $slotId)
            ->update([
                'is_enabled' => $isEnabled,
            ]);

        $this->logManualActivity(
            $village->account,
            $village,
            "Building slot {$slotId} automation ".($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Toggle celebration automation for one village from the compact row button.
     */
    public function toggleVillageCelebrationAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isEnabled = ! (bool) $settings->celebration_enabled;

        $updates = [
            'celebration_enabled' => $isEnabled,
        ];

        if ($isEnabled && ! in_array($settings->celebration_type?->value, [VillageCelebrationType::Small->value, VillageCelebrationType::Great->value], true)) {
            $updates['celebration_type'] = VillageCelebrationType::Small;
        }

        $settings->forceFill($updates)->save();

        $this->logManualActivity(
            $village->account,
            $village,
            'Village celebration automation '.($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Toggle troop training automation for one village from the compact row button.
     */
    public function toggleVillageTroopTrainingAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isEnabled = ! (bool) $settings->troop_training_enabled;

        $settings->forceFill([
            'troop_training_enabled' => $isEnabled,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            'Village troop training automation '.($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Move one schedule entry to the front of its queue, or remove that override.
     */
    public function toggleVillageSchedulePin(int $villageId, string $scheduleKey): void
    {
        if (! $this->isSupportedScheduleKey($scheduleKey)) {
            return;
        }

        $isPinned = false;

        $this->updateVillageConstructionSchedule($villageId, function (array &$schedule) use ($scheduleKey, &$isPinned): void {
            if (in_array($scheduleKey, $schedule['pinned'], true)) {
                $schedule['pinned'] = $this->withoutScheduleKey($schedule['pinned'], $scheduleKey);
                $isPinned = false;

                return;
            }

            $schedule['pinned'] = $this->withoutScheduleKey($schedule['pinned'], $scheduleKey);
            array_unshift($schedule['pinned'], $scheduleKey);
            $isPinned = true;
        }, function () use ($scheduleKey, &$isPinned): string {
            return "Schedule entry {$scheduleKey} ".($isPinned ? 'moved to the front' : 'removed from pinned schedule').' from dashboard.';
        });
    }

    /**
     * Toggle whether one schedule entry blocks later candidates until it can run.
     */
    public function toggleVillageScheduleHold(int $villageId, string $scheduleKey): void
    {
        if (! $this->isSupportedScheduleKey($scheduleKey)) {
            return;
        }

        $heldAfterToggle = false;

        $this->updateVillageConstructionSchedule($villageId, function (array &$schedule) use ($scheduleKey, &$heldAfterToggle): void {
            if (in_array($scheduleKey, $schedule['held'], true)) {
                $schedule['held'] = $this->withoutScheduleKey($schedule['held'], $scheduleKey);
                $heldAfterToggle = false;

                return;
            }

            $schedule['held'][] = $scheduleKey;
            $heldAfterToggle = true;
        }, function () use ($scheduleKey, &$heldAfterToggle): string {
            return "Schedule entry {$scheduleKey} ".($heldAfterToggle ? 'held' : 'released').' from dashboard.';
        });
    }

    /**
     * Queue a manual village sync marker.
     */
    public function requestVillageSync(int $villageId): void
    {
        $village = Village::query()->with('account')->findOrFail($villageId);

        if (! $this->queueVillageSync($village, 'Village-only update requested and queued.')) {
            session()->flash(
                'dashboard-banner',
                SystemSetting::automationEnabled()
                    ? "Village {$village->name} was not queued because its account or village is paused."
                    : 'Program automation is paused. Resume it before requesting a village update.',
            );

            return;
        }

        $this->dashboardRevision = '';

        session()->flash('dashboard-banner', "Village {$village->name} was queued for sync, then village automation.");
    }

    /**
     * Queue one quiet village sync when a visible construction or movement timer elapsed.
     */
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

        $this->dashboardRevision = '';
        $this->skipRender();
    }

    protected function queueVillageSync(Village $village, string $message, bool $useReloadAuto = false): bool
    {
        if (! SystemSetting::automationEnabled()) {
            return false;
        }

        if (! $this->villageCanQueueSync($village)) {
            return false;
        }

        $village->account->forceFill([
            'status' => AccountStatus::Syncing,
            'connection_retry_after' => null,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $village->account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Pending,
            'message' => $message,
            'scheduled_at' => now(),
        ]);

        SyncTravianAccountJob::withChain([
            new RunTravianAutomationJob($village->account->id, $village->id, false, true),
        ])->dispatch($village->account->id, $village->id, true, $useReloadAuto);

        return true;
    }

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

    /**
     * Update the saved construction schedule preferences for one village.
     */
    protected function updateVillageConstructionSchedule(int $villageId, callable $callback, callable $messageResolver): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        if ($this->villageAutomationControlsLocked($village)) {
            $this->skipRender();

            return;
        }

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $schedule = $this->normalizeConstructionSchedule($settings->construction_schedule);

        $callback($schedule);

        $settings->forceFill([
            'construction_schedule' => $schedule,
        ])->save();

        $this->logManualActivity($village->account, $village, $messageResolver());
    }

    /**
     * @return array{pinned: list<string>, held: list<string>}
     */
    protected function normalizeConstructionSchedule(mixed $schedule): array
    {
        if (! is_array($schedule)) {
            return [
                'pinned' => [],
                'held' => [],
            ];
        }

        return [
            'pinned' => $this->normalizeScheduleKeyList($schedule['pinned'] ?? []),
            'held' => $this->normalizeScheduleKeyList($schedule['held'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    protected function normalizeScheduleKeyList(mixed $scheduleKeys): array
    {
        if (! is_array($scheduleKeys)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $scheduleKey): string => is_scalar($scheduleKey) ? (string) $scheduleKey : '', $scheduleKeys),
            fn (string $scheduleKey): bool => $this->isSupportedScheduleKey($scheduleKey),
        )));
    }

    /**
     * @param  list<string>  $scheduleKeys
     * @return list<string>
     */
    protected function withoutScheduleKey(array $scheduleKeys, string $scheduleKey): array
    {
        return array_values(array_filter(
            $scheduleKeys,
            static fn (string $existingScheduleKey): bool => $existingScheduleKey !== $scheduleKey,
        ));
    }

    /**
     * Validate dashboard schedule keys.
     */
    protected function isSupportedScheduleKey(string $scheduleKey): bool
    {
        $parts = explode(':', $scheduleKey);

        if (count($parts) !== 3) {
            return false;
        }

        [$queueKind, $slotId, $target] = $parts;

        if (! in_array($queueKind, ['field', 'building', 'building-target'], true) || ! ctype_digit($slotId) || ! ctype_digit($target)) {
            return false;
        }

        $slotId = (int) $slotId;
        $target = (int) $target;

        if ($queueKind === 'field') {
            return $slotId >= 1 && $slotId <= 18 && $target >= 1 && $target <= 20;
        }

        if ($queueKind === 'building') {
            return $slotId >= 19 && $slotId <= 40 && $target >= 1 && $target <= 20;
        }

        return $slotId >= 19 && $slotId <= 40 && $target >= 1 && $target <= 99;
    }

    protected function villageAutomationControlsLocked(Village $village): bool
    {
        return ! SystemSetting::automationEnabled()
            || ! (bool) $village->is_active
            || ! (bool) $village->account?->is_active;
    }
}
