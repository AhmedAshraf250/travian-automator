<?php

use App\Application\Accounts\Hero\UseHeroResourcesForConstruction;
use App\Models\Village;
use App\Models\VillageResourceState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('hero resource shortages include crop buffer when crop production is negative', function () {
    $village = new Village;
    $village->setRelation('resourceState', new VillageResourceState([
        'wood' => 500,
        'clay' => 500,
        'iron' => 500,
        'crop' => 0,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => -60,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
    ]));

    $method = new ReflectionMethod(UseHeroResourcesForConstruction::class, 'resourceShortages');
    $method->setAccessible(true);

    $shortages = $method->invoke(new UseHeroResourcesForConstruction, $village, [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
    ], [
        'lumberStock' => 500,
        'clayStock' => 500,
        'ironStock' => 500,
        'cropStock' => 0,
    ]);

    expect($shortages)->toMatchArray([
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 15,
    ]);
});
