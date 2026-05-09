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
    expect($overview->activeVillage->population)->toBe(52);
    expect($overview->resourceState->wood)->toBe(1736);
    expect($overview->resourceState->clay)->toBe(1948);
    expect($overview->resourceState->iron)->toBe(1868);
    expect($overview->resourceState->crop)->toBe(1032);
    expect($overview->resourceState->woodProduction)->toBe(76);
    expect($overview->resourceState->cropProduction)->toBe(35);
    expect($overview->resourceState->freeCropProduction)->toBe(0);
    expect($overview->resourceState->warehouseCapacity)->toBe(2300);
    expect($overview->resourceState->granaryCapacity)->toBe(1200);
    expect($overview->runtimeState->tribeId)->toBe(1);
    expect($overview->runtimeState->troopSlots)->toHaveCount(10);
    expect($overview->runtimeState->troopSlots[0])->toBe(0);
    expect($overview->runtimeState->troopSlots[1])->toBe(3);
    expect($overview->runtimeState->incomingAttackCount)->toBe(0);
    expect($overview->runtimeState->outgoingMovementCount)->toBe(1);
    expect($overview->runtimeState->heroStatus)->toBe('returning');
    expect($overview->runtimeState->constructionEntries)->toHaveCount(2);
    expect($overview->constructionQueue)->toHaveCount(2);
    expect($overview->constructionQueue[0]->buildingName)->toBe('حقل القمح');
    expect($overview->constructionQueue[0]->targetLevel)->toBe(2);
    expect($overview->constructionQueue[0]->remainingSeconds)->toBe(311);
    expect($overview->villages)->toHaveCount(1);
});

test('dorf1 overview parser handles the english live sample with movements and construction timing', function () {
    $parser = app(Dorf1OverviewParser::class);
    $html = file_get_contents(base_path('may-help/travian-samples/login/dorf1.php'));

    expect($html)->not->toBeFalse();

    $overview = $parser->parse((string) $html);

    expect($overview->activeVillage->travianVillageId)->toBe('23378');
    expect($overview->activeVillage->name)->toBe('قرية Marshal25');
    expect($overview->activeVillage->x)->toBe(9);
    expect($overview->activeVillage->y)->toBe(60);
    expect($overview->activeVillage->population)->toBe(64);
    expect($overview->runtimeState->troopSlots)->toBe([0, 3, 0, 8, 0, 0, 0, 0, 0, 0]);
    expect($overview->runtimeState->incomingReinforcementCount)->toBe(0);
    expect($overview->runtimeState->outgoingMovementCount)->toBe(1);
    expect($overview->runtimeState->heroStatus)->toBe('adventure');
    expect($overview->runtimeState->heroRemainingSeconds)->toBe(522);
    expect($overview->runtimeState->movementEntries)->toHaveCount(1);
    expect($overview->runtimeState->movementEntries[0]->kind)->toBe('outgoing');
    expect($overview->runtimeState->movementEntries[0]->label)->toBe('1 Adventure');
    expect($overview->runtimeState->movementEntries[0]->remainingLabel)->toBe('0:08:42');
    expect($overview->runtimeState->movementEntries[0]->remainingSeconds)->toBe(522);
    expect($overview->runtimeState->constructionEntries)->toHaveCount(0);
    expect($overview->constructionQueue)->toHaveCount(0);
});
