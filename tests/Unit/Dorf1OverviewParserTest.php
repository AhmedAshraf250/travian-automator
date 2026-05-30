<?php

use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;

test('dorf1 overview parser extracts active village and resources', function () {
    $parser = app(Dorf1OverviewParser::class);
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf1.php.html'));

    expect($html)->not->toBeFalse();

    $overview = $parser->parse((string) $html);

    expect($overview->activeVillage->travianVillageId)->toBe('23378');
    expect($overview->activeVillage->name)->toBe('قرية Marshal25');
    expect($overview->activeVillage->x)->toBe(9);
    expect($overview->activeVillage->y)->toBe(60);
    expect($overview->activeVillage->population)->toBe(82);
    expect($overview->resourceState->wood)->toBe(1993);
    expect($overview->resourceState->clay)->toBe(2121);
    expect($overview->resourceState->iron)->toBe(2202);
    expect($overview->resourceState->crop)->toBe(1530);
    expect($overview->resourceState->woodProduction)->toBe(112);
    expect($overview->resourceState->cropProduction)->toBe(29);
    expect($overview->resourceState->freeCropProduction)->toBe(20);
    expect($overview->resourceState->warehouseCapacity)->toBe(2300);
    expect($overview->resourceState->granaryCapacity)->toBe(1700);
    expect($overview->runtimeState->tribeId)->toBe(1);
    expect($overview->runtimeState->troopSlots)->toHaveCount(11);
    expect($overview->runtimeState->troopSlots[0])->toBe(0);
    expect($overview->runtimeState->troopSlots[1])->toBe(0);
    expect($overview->runtimeState->incomingAttackCount)->toBe(1);
    expect($overview->runtimeState->outgoingMovementCount)->toBe(1);
    expect($overview->runtimeState->heroStatus)->toBe('returning');
    expect($overview->runtimeState->constructionEntries)->toHaveCount(0);
    expect($overview->constructionQueue)->toHaveCount(0);
    expect($overview->fieldSlots)->toHaveCount(18);
    expect($overview->fieldSlots[0]->slotId)->toBe(1);
    expect($overview->fieldSlots[0]->buildingGid)->toBe(1);
    expect($overview->fieldSlots[0]->currentLevel)->toBe(3);
    expect($overview->fieldSlots[9]->slotId)->toBe(10);
    expect($overview->fieldSlots[9]->buildingGid)->toBe(3);
    expect($overview->villages)->toHaveCount(1);
});

test('dorf1 overview parser handles the english live sample with movements and construction timing', function () {
    $parser = app(Dorf1OverviewParser::class);
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/login/dorf1.php'));

    expect($html)->not->toBeFalse();

    $overview = $parser->parse((string) $html);

    expect($overview->activeVillage->travianVillageId)->toBe('23378');
    expect($overview->activeVillage->name)->toBe('قرية Marshal25');
    expect($overview->activeVillage->x)->toBe(9);
    expect($overview->activeVillage->y)->toBe(60);
    expect($overview->activeVillage->population)->toBe(64);
    expect($overview->runtimeState->troopSlots)->toBe([0, 3, 0, 8, 0, 0, 0, 0, 0, 0, 0]);
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
    expect($overview->fieldSlots)->toHaveCount(18);
    expect($overview->activeVillage->switchUri)->toBe('?newdid=23378&');
});

test('dorf1 overview parser keeps the settler slot for every tribe unit range', function () {
    $parser = app(Dorf1OverviewParser::class);
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/login/dorf1.php'));

    expect($html)->not->toBeFalse();

    $settlerRow = <<<'HTML'
                                    <tr>
                                        <td class="ico">
                                            <a href="/build.php?id=39#td">
                                                <img class="unit u20" src="/img/x.gif" alt="Settlers" title="Settlers" />
                                            </a>
                                        </td>
                                        <td class="num">2</td>
                                        <td class="un">Settlers</td>
                                    </tr>

HTML;

    $htmlWithSettlers = preg_replace(
        '/(<table id="troops".*?<tbody>.*?)(\s*<\/tbody>)/s',
        "$1\n{$settlerRow}                                </tbody>",
        (string) $html,
        1,
    );

    expect($htmlWithSettlers)->not->toBeNull();

    $overview = $parser->parse($htmlWithSettlers);

    expect($overview->runtimeState->troopSlots)->toHaveCount(11);
    expect($overview->runtimeState->troopSlots[10])->toBe(2);
});

test('dorf1 overview parser extracts hero first unit and settler from arabic runtime sample', function () {
    $parser = app(Dorf1OverviewParser::class);
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/test02/samp1.md'));

    expect($html)->not->toBeFalse();

    $overview = $parser->parse((string) $html);

    expect($overview->runtimeState->troopSlots)->toBe([1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1]);
    expect($overview->runtimeState->serverReportedAt?->getTimestamp())->toBe(1779556526);
});
