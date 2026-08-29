<?php

use App\Application\Travian\TravianTroopCatalog;

test('catalog exposes eight stable unit definitions for every supported tribe', function () {
    expect(TravianTroopCatalog::definitions())->toHaveCount(24)
        ->and(TravianTroopCatalog::definitionsForTribe(1))->toHaveCount(8)
        ->and(TravianTroopCatalog::definitionsForTribe(2))->toHaveCount(8)
        ->and(TravianTroopCatalog::definitionsForTribe(3))->toHaveCount(8)
        ->and(TravianTroopCatalog::definitionsForTribe(4))->toBe([]);
});

test('catalog keeps global unit ids separate from tribe relative form fields', function () {
    $maceman = TravianTroopCatalog::definition(11);

    expect($maceman)
        ->not->toBeNull()
        ->and($maceman['unit_key'])->toBe('u11')
        ->and($maceman['tribe_key'])->toBe('t1')
        ->and(TravianTroopCatalog::unitIdForTribeSlot(2, 1))->toBe(11)
        ->and(TravianTroopCatalog::unitIdForTribeSlot(1, 1))->toBe(1);
});

test('catalog separates training cost from crop upkeep and gates workshop execution', function () {
    $legionnaire = TravianTroopCatalog::definition(1);
    $romanRam = TravianTroopCatalog::definition(7);

    expect($legionnaire['training_cost'])->toBe([
        'wood' => 120,
        'clay' => 100,
        'iron' => 150,
        'crop' => 30,
    ])->and($legionnaire['crop_upkeep'])->toBe(1)
        ->and($romanRam['training_building_gid'])->toBe(21)
        ->and($romanRam['training_supported'])->toBeFalse();
});
