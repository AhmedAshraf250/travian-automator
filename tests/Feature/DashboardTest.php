<?php

use App\Jobs\SyncTravianAccountJob;
use App\Livewire\Dashboard\Index;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use App\Models\ImportDraft;
use App\Models\SystemSetting;
use App\Models\VillageBuildingTarget;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('dashboard page loads successfully', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Travian Multi-Account Automation');
});

test('bulk import creates accounts and persists encrypted draft', function () {
    Livewire::test(Index::class)
        ->set('bulkImportDraft', '!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0')
        ->call('importAccounts');

    $account = Account::query()->first();

    expect($account)->not->toBeNull();
    expect($account?->username)->toBe('marshal');
    expect($account?->proxy_ip)->toBe('127.0.0.1');
    expect($account?->proxy_port)->toBe(8080);
    expect(AccountSetting::query()->count())->toBe(1);
    expect(ActivityLog::query()->count())->toBe(1);
    expect(ImportDraft::query()->where('key', 'bulk-account-import')->exists())->toBeTrue();
});

test('dashboard shows imported account username', function () {
    $account = Account::factory()->create([
        'username' => 'strategist',
    ]);

    $account->settings()->create([
        'resource_priorities' => [15, 11, 1, 1],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('strategist');
});

test('village update queues the account overview sync job', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('requestVillageSync', $village->id);

    Queue::assertPushed(SyncTravianAccountJob::class, function (SyncTravianAccountJob $job) use ($account, $village) {
        return $job->accountId === $account->id
            && $job->villageId === $village->id;
    });
});

test('bulk import archives managed accounts removed from the latest snapshot', function () {
    Livewire::test(Index::class)
        ->set('bulkImportDraft', implode("\n", [
            '!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0',
            '!https://ts7.x1.arabics.travian.com/!strategist!12345678!!!Mozilla/5.0',
        ]))
        ->call('importAccounts');

    Livewire::test(Index::class)
        ->set('bulkImportDraft', '!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0')
        ->call('importAccounts');

    $archivedAccount = Account::query()->where('username', 'strategist')->first();

    expect($archivedAccount)->not->toBeNull();
    expect($archivedAccount?->is_archived)->toBeTrue();
    expect($archivedAccount?->is_active)->toBeFalse();
    expect(Account::query()->where('is_archived', false)->pluck('username')->all())->toBe(['marshal']);
});

test('dashboard toggles the global automation switch', function () {
    expect(SystemSetting::automationEnabled())->toBeTrue();

    Livewire::test(Index::class)
        ->call('toggleAutomation');

    expect(SystemSetting::automationEnabled())->toBeFalse();

    Livewire::test(Index::class)
        ->call('toggleAutomation');

    expect(SystemSetting::automationEnabled())->toBeTrue();
});

test('dashboard saves the global fallback user agent setting', function () {
    Livewire::test(Index::class)
        ->set('defaultUserAgent', 'Mozilla/5.0 Test Global Agent')
        ->call('saveProgramSettings')
        ->assertHasNoErrors();

    expect(SystemSetting::defaultUserAgent())->toBe('Mozilla/5.0 Test Global Agent');
});

test('dashboard shows the inherited global fallback user agent for accounts without one', function () {
    SystemSetting::setDefaultUserAgent('Mozilla/5.0 Shared Agent');

    $account = Account::factory()->create([
        'username' => 'marshal',
        'user_agent' => null,
    ]);

    $account->settings()->create([
        'resource_priorities' => [15, 11, 1, 1],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Mozilla/5.0 Shared Agent')
        ->assertSee('Inherited from program settings when available');
});

test('dashboard toggles village field and building automation flags', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('toggleVillageFieldsAutomation', $village->id)
        ->call('toggleVillageBuildingsAutomation', $village->id);

    $village->refresh();

    expect($village->settings)->not->toBeNull();
    expect($village->settings?->field_priority)->toBe(VillageSetting::defaultFieldPriority());
    expect($village->settings?->pause_fields)->toBeTrue();
    expect($village->settings?->pause_buildings)->toBeTrue();
});

test('dashboard opens the village settings modal with existing slot data', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => [
            'wood' => 4,
            'clay' => 2,
            'iron' => 1,
            'crop' => 3,
        ],
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 8,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 12,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->assertSet('showVillageBuildPlanModal', true)
        ->assertSet('editingVillageId', $village->id)
        ->assertSet('editingVillageTribeLabel', 'Roman')
        ->assertSet('villageFieldsAutomationDraft', false)
        ->assertSet('villageBuildingsAutomationDraft', true)
        ->assertSet('villageFieldPriorityDraft.wood', 4)
        ->assertSet('villageBuildingPlanDraft.26.current_gid', 15)
        ->assertSet('villageBuildingPlanDraft.26.target_level', 12);
});

test('dashboard saves village field priorities, automation toggles, and building targets from the settings modal', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageFieldsAutomationDraft', false)
        ->set('villageBuildingsAutomationDraft', true)
        ->set('villageFieldPriorityDraft', [
            'wood' => 4,
            'clay' => 1,
            'iron' => 2,
            'crop' => 3,
        ])
        ->set('villageBuildingPlanDraft.26.building_gid', 15)
        ->set('villageBuildingPlanDraft.26.target_level', 7)
        ->set('villageBuildingPlanDraft.26.priority', 1)
        ->set('villageBuildingPlanDraft.26.is_enabled', true)
        ->set('villageBuildingPlanDraft.21.building_gid', 10)
        ->set('villageBuildingPlanDraft.21.target_level', 5)
        ->set('villageBuildingPlanDraft.21.priority', 2)
        ->set('villageBuildingPlanDraft.21.is_enabled', true)
        ->call('saveVillageSettings')
        ->assertHasNoErrors()
        ->assertSet('showVillageBuildPlanModal', false);

    $savedSettings = $village->fresh()->settings;

    expect($savedSettings?->field_priority)->toBe([
        'wood' => 4,
        'clay' => 1,
        'iron' => 2,
        'crop' => 3,
    ]);
    expect($savedSettings?->pause_fields)->toBeTrue();
    expect($savedSettings?->pause_buildings)->toBeFalse();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 26)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 15,
        'target_level' => 7,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 21)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 10,
        'target_level' => 5,
        'priority' => 2,
        'is_enabled' => true,
    ]);
});

test('dashboard allows duplicate field priorities in village settings', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23382',
        'name' => 'قرية Balanced',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageFieldPriorityDraft', [
            'wood' => 1,
            'clay' => 1,
            'iron' => 2,
            'crop' => 2,
        ])
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect($village->fresh()->settings?->field_priority)->toBe([
        'wood' => 1,
        'clay' => 1,
        'iron' => 2,
        'crop' => 2,
    ]);
});

test('dashboard rejects changing an occupied slot to a different building gid', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.26.building_gid', 10)
        ->set('villageBuildingPlanDraft.26.target_level', 6)
        ->call('saveVillageSettings')
        ->assertHasErrors('villageBuildingPlanDraft.26.building_gid');

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 26)
            ->exists()
    )->toBeFalse();
});

test('dashboard allows keeping the current building level as the final target', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.26.building_gid', 15)
        ->set('villageBuildingPlanDraft.26.target_level', 5)
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 26)
            ->first()
            ?->target_level
    )->toBe(5);
});

test('dashboard ignores stale completed building targets while saving another slot', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 19,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 9,
    ]);
    $village->buildings()->create([
        'slot_id' => 20,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 3,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 19,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'target_level' => 6,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.20.building_gid', 23)
        ->set('villageBuildingPlanDraft.20.target_level', 7)
        ->set('villageBuildingPlanDraft.20.priority', 2)
        ->set('villageBuildingPlanDraft.20.is_enabled', true)
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 19)
            ->exists()
    )->toBeFalse();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 20)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 23,
        'target_level' => 7,
        'priority' => 2,
        'is_enabled' => true,
    ]);
});

test('dashboard shows construction countdown and finish time in the village row and activity log', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $account->settings()->create([
        'resource_priorities' => [15, 11, 1, 1],
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23390',
        'name' => 'قرية Timer',
        'x' => 9,
        'y' => 60,
        'population' => 89,
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->resourceState()->create([
        'wood' => 1800,
        'clay' => 1700,
        'iron' => 1600,
        'crop' => 1500,
        'wood_production' => 140,
        'clay_production' => 120,
        'iron_production' => 104,
        'crop_production' => 37,
        'warehouse_capacity' => 4000,
        'granary_capacity' => 1700,
        'simulated_at' => $now,
        'server_reported_at' => $now,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => array_fill(0, 11, 0),
        'movement_entries' => [],
        'construction_entries' => [
            [
                'building_name' => 'حفرة الطين',
                'target_level' => 4,
                'remaining_seconds' => 600,
                'remaining_label' => '0:10:00',
                'finish_label' => '22:25',
            ],
        ],
        'server_reported_at' => $now,
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'village_id' => $village->id,
        'activity_type' => 'build',
        'status' => 'done',
        'payload' => [
            'building_name' => 'حفرة الطين',
            'target_level' => 4,
            'remaining_seconds' => 600,
            'remaining_label' => '0:10:00',
            'finish_label' => '22:25',
        ],
        'message' => 'Field upgrade order issued successfully.',
        'executed_at' => $now,
    ]);

    Livewire::test(Index::class)
        ->set("expandedAccounts.{$account->id}", true)
        ->assertSee('[0,0,0,0,0,0,0,0,0,0,0]')
        ->assertSee('W 1800/4000')
        ->assertSee('+140/h')
        ->assertSee('0:10:00')
        ->assertSee('Ends 22:25');

    Carbon::setTestNow();
});
