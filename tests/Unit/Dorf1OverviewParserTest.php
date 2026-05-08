<?php

use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;

test('dorf1 overview parser extracts active village and resources', function () {
    $parser = app(Dorf1OverviewParser::class);
    $html = file_get_contents(base_path('may-help/travian-samples/dorf1.php.html'));

    expect($html)->not->toBeFalse();

    $overview = $parser->parse((string) $html);

    expect($overview->activeVillage->travianVillageId)->toBe('23378');
    expect($overview->activeVillage->name)->toBe('قرية Marshal25');
    expect($overview->activeVillage->x)->toBe(9);
    expect($overview->activeVillage->y)->toBe(60);
    expect($overview->activeVillage->population)->toBe(8);
    expect($overview->resourceState->wood)->toBe(504);
    expect($overview->resourceState->clay)->toBe(394);
    expect($overview->resourceState->iron)->toBe(544);
    expect($overview->resourceState->crop)->toBe(634);
    expect($overview->resourceState->woodProduction)->toBe(58);
    expect($overview->resourceState->cropProduction)->toBe(56);
    expect($overview->resourceState->freeCropProduction)->toBe(16);
    expect($overview->resourceState->warehouseCapacity)->toBe(800);
    expect($overview->resourceState->granaryCapacity)->toBe(800);
    expect($overview->villages)->toHaveCount(1);
});
