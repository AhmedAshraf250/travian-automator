<?php

use App\Application\Accounts\Automation\PlanNextAccountAutomation;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('planner schedules an account shortly after the next known timer', function () {
    config()->set('travian.automation.idle_minutes', 10);
    config()->set('travian.automation.timer_grace_seconds', 45);

    $now = now();
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Timed Village',
        'is_active' => true,
        'last_sync_at' => $now,
    ]);
    $village->runtimeState()->create([
        'construction_entries' => [
            [
                'building_name' => 'Woodcutter',
                'target_level' => 5,
                'remaining_seconds' => 120,
            ],
        ],
        'movement_entries' => [
            [
                'kind' => 'outgoing',
                'label' => 'Adventure',
                'remaining_seconds' => 300,
            ],
        ],
        'server_reported_at' => $now,
    ]);

    $nextRunAt = app(PlanNextAccountAutomation::class)->handle($account);

    expect($nextRunAt->betweenIncluded($now->copy()->addSeconds(160), $now->copy()->addSeconds(170)))->toBeTrue();
});

test('planner marks accounts with missing village snapshots as immediately due', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);
    $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Unsynced Village',
        'is_active' => true,
        'last_sync_at' => null,
    ]);

    $nextRunAt = app(PlanNextAccountAutomation::class)->handle($account);

    expect($nextRunAt->lessThanOrEqualTo(now()->addSecond()))->toBeTrue();
});

test('planner probes soon when a roman village has a building timer but the field lane is open', function () {
    $now = now();
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
        'last_sync_at' => $now,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'construction_entries' => [
            [
                'building_name' => 'مخزن',
                'target_level' => 5,
                'remaining_seconds' => 3600,
            ],
        ],
        'movement_entries' => [],
        'server_reported_at' => $now,
    ]);

    $nextRunAt = app(PlanNextAccountAutomation::class)->handle($account);

    expect($nextRunAt->lessThanOrEqualTo($now->copy()->addSecond()))->toBeTrue();
});

test('planner waits for known resource shortage when the open lane cannot build yet', function () {
    Carbon::setTestNow('2026-05-30 10:00:00');
    config()->set('travian.automation.timer_grace_seconds', 45);

    $now = now();
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '12346',
        'name' => 'Shortage Village',
        'is_active' => true,
        'last_sync_at' => $now,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 2,
        'construction_entries' => [],
        'construction_resource_shortages' => [
            [
                'queue_kind' => 'field',
                'slot_id' => 1,
                'building_gid' => 1,
                'target_level' => 4,
                'resource_ready_seconds' => 1329,
                'resource_ready_at' => $now->copy()->addSeconds(1329)->toISOString(),
                'recorded_at' => $now->toISOString(),
            ],
        ],
        'movement_entries' => [],
        'server_reported_at' => $now,
    ]);

    $nextRunAt = app(PlanNextAccountAutomation::class)->handle($account);

    expect($nextRunAt->equalTo($now->copy()->addSeconds(1374)))->toBeTrue();

    Carbon::setTestNow();
});

test('planner subtracts elapsed time from server reported timers', function () {
    config()->set('travian.automation.timer_grace_seconds', 45);

    $now = now();
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Elapsed Village',
        'is_active' => true,
        'last_sync_at' => $now,
    ]);
    $village->runtimeState()->create([
        'construction_entries' => [
            [
                'building_name' => 'Woodcutter',
                'target_level' => 5,
                'remaining_seconds' => 300,
            ],
        ],
        'movement_entries' => [],
        'server_reported_at' => $now->copy()->subSeconds(120),
    ]);

    $nextRunAt = app(PlanNextAccountAutomation::class)->handle($account);

    expect($nextRunAt->betweenIncluded($now->copy()->addSeconds(220), $now->copy()->addSeconds(230)))->toBeTrue();
});

test('planner schedules around active hero movement timers', function () {
    Carbon::setTestNow('2026-05-30 10:00:00');
    config()->set('travian.automation.timer_grace_seconds', 45);

    $now = now();
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);
    $account->settings()->create([
        'resource_priorities' => AccountSetting::defaultResourcePriorities(),
        'hero_use_global_settings' => false,
        'hero_adventures_enabled' => true,
        'hero_min_health' => 40,
    ]);
    $account->heroState()->create([
        'status' => 'adventure',
        'health_percent' => 95,
        'adventures_available_count' => 0,
        'hero_remaining_seconds' => 300,
        'seen_at' => $now,
    ]);

    $nextRunAt = app(PlanNextAccountAutomation::class)->handle($account);

    expect($nextRunAt->equalTo($now->copy()->addSeconds(345)))->toBeTrue();

    Carbon::setTestNow();
});

test('planner does not loop immediately when hero adventure is blocked by health', function () {
    Carbon::setTestNow('2026-05-30 10:00:00');
    config()->set('travian.automation.idle_minutes', 10);

    $now = now();
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);
    $account->settings()->create([
        'resource_priorities' => AccountSetting::defaultResourcePriorities(),
        'hero_use_global_settings' => false,
        'hero_adventures_enabled' => true,
        'hero_min_health' => 40,
    ]);
    $account->heroState()->create([
        'status' => 'home',
        'health_percent' => 20,
        'adventures_available_count' => 2,
        'seen_at' => $now,
    ]);

    $nextRunAt = app(PlanNextAccountAutomation::class)->handle($account);

    expect($nextRunAt->equalTo($now->copy()->addMinutes(10)))->toBeTrue();

    Carbon::setTestNow();
});
