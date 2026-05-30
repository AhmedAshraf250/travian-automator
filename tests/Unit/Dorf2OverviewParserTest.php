<?php

use App\Application\Accounts\Sync\Parsers\Dorf2OverviewParser;

test('dorf2 overview parser extracts village building slots and deduplicates the wall slot', function () {
    $parser = app(Dorf2OverviewParser::class);
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf2.php.html'));

    expect($html)->not->toBeFalse();

    $overview = $parser->parse((string) $html);
    $wallSlot = collect($overview->buildingSlots)->firstWhere('slotId', 40);
    $emptySlot = collect($overview->buildingSlots)->firstWhere('slotId', 30);

    expect($overview->buildingSlots)->toHaveCount(22);
    expect($overview->buildingSlots[0]->slotId)->toBe(19);
    expect($overview->buildingSlots[0]->buildingGid)->toBe(10);
    expect($overview->buildingSlots[0]->buildingName)->toBe('مخزن');
    expect($overview->buildingSlots[0]->currentLevel)->toBe(3);
    expect(collect($overview->buildingSlots)->where('slotId', 40))->toHaveCount(1);
    expect($wallSlot)->not->toBeNull();
    expect($wallSlot?->buildingGid)->toBe(31);
    expect($wallSlot?->currentLevel)->toBe(2);
    expect($emptySlot)->not->toBeNull();
    expect($emptySlot?->buildingGid)->toBe(0);
    expect($emptySlot?->currentLevel)->toBe(0);
    expect($emptySlot?->isEmpty)->toBeTrue();
});
