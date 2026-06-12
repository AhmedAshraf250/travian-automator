<?php

use App\Jobs\RunTravianAutomationJob;
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

test('village update queues village sync followed by village automation', function () {
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

    Queue::assertPushedWithChain(SyncTravianAccountJob::class, [
        new RunTravianAutomationJob($account->id, $village->id, false),
    ]);
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

test('dashboard keeps accounts in the same order as the latest bulk import', function () {
    Livewire::test(Index::class)
        ->set('bulkImportDraft', implode("\n", [
            '!https://ts7.x1.arabics.travian.com/!third!12345678!!!Mozilla/5.0',
            '!https://ts7.x1.arabics.travian.com/!first!12345678!!!Mozilla/5.0',
            '!https://ts7.x1.arabics.travian.com/!second!12345678!!!Mozilla/5.0',
        ]))
        ->call('importAccounts');

    Account::query()->where('username', 'first')->update(['last_sync_at' => now()]);

    expect(Account::query()->orderBy('import_position')->pluck('username')->all())->toBe([
        'third',
        'first',
        'second',
    ]);

    Livewire::test(Index::class)
        ->assertSeeInOrder(['third', 'first', 'second']);
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
        ->set('globalFieldPriorityDraft', [
            'wood' => 2,
            'clay' => 1,
            'iron' => 3,
            'crop' => 4,
        ])
        ->set('globalPrioritizeCropFieldsWhenNegativeDraft', false)
        ->call('saveProgramSettings')
        ->assertHasNoErrors();

    expect(SystemSetting::defaultUserAgent())->toBe('Mozilla/5.0 Test Global Agent');
    expect(SystemSetting::constructionDefaults()['field_priority'])->toBe([
        'wood' => 2,
        'clay' => 1,
        'iron' => 3,
        'crop' => 4,
    ]);
    expect(SystemSetting::constructionDefaults()['prioritize_crop_fields_when_negative'])->toBeFalse();
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
        ->assertSee('Mozilla/5.0 Shared Agent');
});

test('dashboard saves per account user agent and hero settings', function () {
    $account = Account::factory()->create([
        'username' => 'hero-owner',
        'user_agent' => null,
    ]);
    $account->settings()->create([
        'resource_priorities' => [15, 11, 1, 1],
    ]);

    Livewire::test(Index::class)
        ->call('openAccountSettingsModal', $account->id)
        ->assertSet('showAccountSettingsModal', true)
        ->set('accountInheritUserAgentDraft', false)
        ->set('accountUserAgentDraft', 'Mozilla/5.0 Account Hero Agent')
        ->set('accountAcceptQuestsDraft', false)
        ->set('accountHeroUseGlobalSettingsDraft', false)
        ->set('accountHeroAdventuresEnabledDraft', true)
        ->set('accountHeroMinHealthDraft', 55)
        ->set('accountHeroReviveEnabledDraft', true)
        ->set('accountHeroAttributeUpgradeEnabledDraft', true)
        ->set('accountHeroAttributeWeightsDraft', [
            'power' => 1,
            'offBonus' => 2,
            'defBonus' => 3,
            'productionPoints' => 4,
        ])
        ->call('saveAccountSettings')
        ->assertHasNoErrors()
        ->assertSet('showAccountSettingsModal', false);

    $account->refresh();
    $settings = $account->settings()->first();

    expect($account->user_agent)->toBe('Mozilla/5.0 Account Hero Agent')
        ->and($settings?->accept_quests)->toBeFalse()
        ->and($settings?->hero_use_global_settings)->toBeFalse()
        ->and($settings?->hero_adventures_enabled)->toBeTrue()
        ->and($settings?->hero_min_health)->toBe(55)
        ->and($settings?->hero_revive_enabled)->toBeTrue()
        ->and($settings?->hero_attribute_upgrade_enabled)->toBeTrue()
        ->and($settings?->hero_attribute_weights)->toBe([
            'power' => 1,
            'offBonus' => 2,
            'defBonus' => 3,
            'productionPoints' => 4,
        ]);
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
        'celebration_enabled' => true,
        'celebration_type' => 'great',
        'celebration_min_culture_points' => 300,
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
    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 10,
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
        ->assertSet('villageInheritProgramPriorityDraft', true)
        ->assertSet('villageSendResourcesDraft', true)
        ->assertSet('villageCelebrationEnabledDraft', true)
        ->assertSet('villageCelebrationTypeDraft', 'great')
        ->assertSet('villageCelebrationMinimumCulturePointsDraft', 300)
        ->assertSet('villagePrioritizeCropFieldsWhenNegativeDraft', true)
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
    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 10,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageFieldsAutomationDraft', false)
        ->set('villageBuildingsAutomationDraft', true)
        ->set('villageInheritProgramPriorityDraft', false)
        ->set('villageSendResourcesDraft', false)
        ->set('villageCelebrationEnabledDraft', true)
        ->set('villageCelebrationTypeDraft', 'great')
        ->set('villageCelebrationMinimumCulturePointsDraft', 300)
        ->set('villageTroopTrainingEnabledDraft', true)
        ->set('villagePrioritizeCropFieldsWhenNegativeDraft', false)
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
    expect($savedSettings?->inherit_from_account)->toBeFalse();
    expect($savedSettings?->send_enabled)->toBeFalse();
    expect($savedSettings?->troop_training_enabled)->toBeTrue();
    expect($savedSettings?->celebration_enabled)->toBeTrue();
    expect($savedSettings?->celebration_type?->value)->toBe('great');
    expect($savedSettings?->celebration_min_culture_points)->toBe(300);
    expect($savedSettings?->prioritize_crop_fields_when_negative)->toBeFalse();

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

test('dashboard blocks village celebrations until a town hall exists', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'قرية بدون بلدية',
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

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageCelebrationEnabledDraft', true)
        ->assertSet('villageCelebrationReadinessMessage', 'Cannot enable celebrations yet: this village does not have a Town Hall.')
        ->call('saveVillageSettings')
        ->assertHasErrors('villageCelebrationEnabledDraft');
});

test('dashboard blocks great celebrations until town hall level ten', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23380',
        'name' => 'قرية بلدية صغيرة',
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
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageCelebrationEnabledDraft', true)
        ->set('villageCelebrationTypeDraft', 'great')
        ->assertSet('villageCelebrationReadinessMessage', 'Cannot use Great celebrations yet: Town Hall is level 5, and level 10 is required.')
        ->call('saveVillageSettings')
        ->assertHasErrors('villageCelebrationTypeDraft');
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
        'troop_training_enabled' => true,
        'celebration_enabled' => true,
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
        'incoming_reinforcement_count' => 1,
        'movement_entries' => [
            [
                'kind' => 'incoming_reinforcement',
                'label' => 'تعزيز',
                'remaining_seconds' => 0,
                'remaining_label' => '0:00:00',
            ],
        ],
        'construction_entries' => [
            [
                'building_name' => 'حفرة الطين',
                'target_level' => 4,
                'remaining_seconds' => 600,
                'remaining_label' => '0:10:00',
                'finish_label' => '22:25',
            ],
            [
                'building_name' => 'حفرة منتهية',
                'target_level' => 6,
                'remaining_seconds' => 0,
                'remaining_label' => '0:00:00',
                'finish_label' => '19:53',
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
        ->assertSee('Troops')
        ->assertSee('assets/troops-icons/hero.png')
        ->assertSee('assets/troops-icons/u1.png')
        ->assertSee('assets/res-icons/lumber_small.png')
        ->assertSee('4000 /')
        ->assertSee('1800')
        ->assertSee('+140/h')
        ->assertSee('#11883d')
        ->assertSee('#f88c1f')
        ->assertSee('T ON')
        ->assertSee('C ON')
        ->assertSee('0:10:00')
        ->assertSee('Ends 22:25')
        ->assertDontSee('حفرة منتهية')
        ->assertDontSee('تعزيز')
        ->assertDontSee('0:00:00');

    Carbon::setTestNow();
});

test('dashboard account row uses recent account activity when it is newer than the last sync time', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
        'last_sync_at' => $now->subHours(2),
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23392',
        'name' => 'CR7',
        'is_active' => true,
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'village_id' => $village->id,
        'activity_type' => 'build',
        'status' => 'done',
        'message' => 'Field upgrade order issued successfully.',
        'executed_at' => $now->subMinutes(8),
    ]);

    Livewire::test(Index::class)
        ->assertSee('8 minutes ago')
        ->assertDontSee('2 hours ago');

    Carbon::setTestNow();
});

test('dashboard account row ignores internal activity logs when showing the last account contact', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
        'last_sync_at' => $now->subHours(2),
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'activity_type' => 'manual',
        'status' => 'done',
        'message' => 'Account activated from dashboard.',
        'executed_at' => $now->subMinutes(3),
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'activity_type' => 'build',
        'status' => 'running',
        'message' => 'Checking local account automation plan.',
        'executed_at' => $now->subMinutes(1),
    ]);

    Livewire::test(Index::class)
        ->set('showActivityLog', false)
        ->assertSee('2 hours ago')
        ->assertDontSee('1 minute ago')
        ->assertDontSee('3 minutes ago');

    Carbon::setTestNow();
});

test('dashboard styles outgoing attack movements with the attack icon palette', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23391',
        'name' => 'قرية Movements',
        'x' => 9,
        'y' => 60,
        'population' => 120,
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => array_fill(0, 11, 0),
        'outgoing_movement_count' => 1,
        'movement_entries' => [
            [
                'kind' => 'outgoing',
                'label' => '1 هجوم',
                'remaining_seconds' => 300,
                'remaining_label' => '0:05:00',
            ],
        ],
        'construction_entries' => [],
        'server_reported_at' => $now,
    ]);

    Livewire::test(Index::class)
        ->set("expandedAccounts.{$account->id}", true)
        ->assertSee('assets/movements-icons/att2.gif')
        ->assertSee('#fff6b5')
        ->assertSee('1 هجوم');

    Carbon::setTestNow();
});
