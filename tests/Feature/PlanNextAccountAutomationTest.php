<?php

use App\Application\Accounts\Automation\PlanNextAccountAutomation;
use App\Models\Account;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
