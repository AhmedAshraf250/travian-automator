<?php

use App\Application\Travian\TravianBuildingCatalog;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('travian building catalog distinguishes field and building queue kinds correctly', function () {
    expect(TravianBuildingCatalog::isFieldGid(1))->toBeTrue();
    expect(TravianBuildingCatalog::isFieldGid(15))->toBeFalse();
    expect(TravianBuildingCatalog::queueKindForName('الحطاب'))->toBe('field');
    expect(TravianBuildingCatalog::queueKindForName('المبنى الرئيسي'))->toBe('building');
});

test('travian building catalog exposes construction rules and fixed slots', function () {
    expect(TravianBuildingCatalog::fixedSlotForGid(16, 1))->toBe(39);
    expect(TravianBuildingCatalog::fixedSlotForGid(32, 2))->toBe(40);
    expect(TravianBuildingCatalog::maxLevelForGid(5))->toBe(5);
    expect(TravianBuildingCatalog::maxLevelForGid(23))->toBe(10);
    expect(TravianBuildingCatalog::finalLevelForGid(10))->toBe(20);
    expect(TravianBuildingCatalog::finalLevelForGid(23))->toBe(10);
    expect(TravianBuildingCatalog::isResourceBonusBuilding(8))->toBeTrue();
    expect(TravianBuildingCatalog::isResourceBonusBuilding(23))->toBeFalse();
    expect(TravianBuildingCatalog::defaultManagedTargetLevelForGid(8))->toBe(5);
    expect(TravianBuildingCatalog::defaultManagedTargetLevelForGid(10))->toBe(20);
    expect(TravianBuildingCatalog::defaultManagedTargetLevelForGid(11))->toBe(20);
    expect(TravianBuildingCatalog::defaultManagedTargetLevelForGid(15))->toBe(14);
    expect(TravianBuildingCatalog::allowsOnlyOneUntilMax(10))->toBeTrue();
    expect(TravianBuildingCatalog::allowsOnlyOneUntilMax(23))->toBeTrue();
    expect(TravianBuildingCatalog::allowsOnlyOneUntilMax(8))->toBeFalse();
    expect(TravianBuildingCatalog::levelOneCostForGid(24))->toMatchArray([
        'wood' => 1250,
        'clay' => 1110,
        'iron' => 1260,
        'crop' => 600,
        'crop_consumption' => 4,
        'total_resources' => 4220,
    ]);
    expect(TravianBuildingCatalog::requirementsForGid(41))->toBe([
        ['gid' => 20, 'level' => 20],
        ['gid' => 16, 'level' => 10],
    ]);
});

test('travian building catalog validates village construction eligibility', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '10001',
        'name' => 'Capital',
        'is_active' => true,
        'is_capital' => true,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
    ]);

    foreach ([
        ['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 10],
        ['slot_id' => 31, 'building_gid' => 22, 'building_type' => 'الأكاديمية', 'current_level' => 10],
        ['slot_id' => 32, 'building_gid' => 17, 'building_type' => 'السوق', 'current_level' => 2],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    expect(TravianBuildingCatalog::canConstructInVillage(24, $account, $village->fresh(['runtimeState', 'buildings']))->allowed)->toBeTrue();
    expect(TravianBuildingCatalog::canConstructInVillage(41, $account, $village->fresh(['runtimeState', 'buildings']))->missingRequirements)->toMatchArray([
        ['gid' => 20, 'name' => 'الإسطبل', 'required_level' => 20, 'current_level' => 0],
        ['gid' => 16, 'name' => 'نقطة التجمع', 'required_level' => 10, 'current_level' => 0],
    ]);
    expect(TravianBuildingCatalog::canConstructInVillage(38, $account, $village->fresh(['runtimeState', 'buildings']))->missingRequirements)->toContain([
        'gid' => 40,
        'name' => null,
        'required_level' => 0,
        'current_level' => 0,
    ]);
});

test('travian building catalog prevents palace conflicts', function () {
    $account = Account::factory()->create();
    $capital = $account->villages()->create([
        'travian_village_id' => '10002',
        'name' => 'Capital',
        'is_active' => true,
        'is_capital' => true,
    ]);
    $otherVillage = $account->villages()->create([
        'travian_village_id' => '10003',
        'name' => 'Other',
        'is_active' => true,
        'is_capital' => false,
    ]);

    foreach ([$capital, $otherVillage] as $village) {
        $village->runtimeState()->create([
            'tribe_id' => 1,
            'troop_slots' => [],
            'movement_entries' => [],
            'construction_entries' => [],
        ]);
    }

    $capital->buildings()->create(['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 5]);
    $capital->buildings()->create(['slot_id' => 31, 'building_gid' => 18, 'building_type' => 'السفارة', 'current_level' => 1]);
    $otherVillage->buildings()->create(['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 5]);
    $otherVillage->buildings()->create(['slot_id' => 31, 'building_gid' => 18, 'building_type' => 'السفارة', 'current_level' => 1]);

    expect(TravianBuildingCatalog::canConstructInVillage(26, $account, $otherVillage->fresh(['runtimeState', 'buildings']))->blockedReason)->toBe('capital_required');

    $otherVillage->buildings()->create(['slot_id' => 32, 'building_gid' => 26, 'building_type' => 'القصر', 'current_level' => 1]);

    expect(TravianBuildingCatalog::canConstructInVillage(26, $account, $capital->fresh(['runtimeState', 'buildings']))->blockedReason)->toBe('account_unique_building_exists');

    $capital->buildings()->create(['slot_id' => 33, 'building_gid' => 25, 'building_type' => 'السكن', 'current_level' => 1]);

    expect(TravianBuildingCatalog::canConstructInVillage(26, $account, $capital->fresh(['runtimeState', 'buildings']))->blockedReason)->toBe('mutually_exclusive_building_exists');
});
