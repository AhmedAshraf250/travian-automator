<?php

use App\Application\Accounts\Troops\Parsers\AcademyPageParser;
use App\Application\Accounts\Troops\Parsers\SmithyPageParser;
use App\Application\Accounts\Troops\Parsers\TrainingBuildingPageParser;

test('training parser reads actionable units, dynamic form values and the active queue by structural classes', function () {
    $html = <<<'HTML'
    <form action="/build.php?id=34&amp;gid=19" method="post">
      <input type="hidden" name="action" value="trainTroops"><input type="hidden" name="checksum" value="fresh-token">
      <div class="action troop troopt1 empty"><img class="unit u1"><input name="t1"></div>
      <div class="action troop troopt1">
        <img class="unit u1"><div data-troopID="1"><span class="level">2</span><input name="t1"></div>
        <div class="resourceWrapper charges">
          <span class="resource"><i class="r1Big"></i><span class="value">120</span></span>
          <span class="resource"><i class="r2Big"></i><span class="value">100</span></span>
          <span class="resource"><i class="r3Big"></i><span class="value">150</span></span>
          <span class="resource"><i class="r4Big"></i><span class="value">30</span></span>
        </div>
        <i class="cropConsumptionBig"></i><span class="value">1</span>
        <span class="duration"><span class="value">0:17:30</span></span>
        <a onclick="$('.val(12)')">12</a>
      </div>
    </form>
    <table class="under_progress"><tbody><tr><td><img class="unit u1"></td><td class="desc">5 الجندي الأول</td><td class="dur"><span class="timer" value="245">0:04:05</span></td></tr></tbody></table>
    HTML;

    $page = app(TrainingBuildingPageParser::class)->parse($html);
    $unit = $page->units[0];

    expect($page->actionUri)->toBe('/build.php?id=34&gid=19')
        ->and($page->hiddenFields)->toMatchArray(['action' => 'trainTroops', 'checksum' => 'fresh-token'])
        ->and($page->units)->toHaveCount(1)
        ->and($unit->unitId)->toBe(1)
        ->and($unit->inputName)->toBe('t1')
        ->and($unit->smithyLevel)->toBe(2)
        ->and($unit->maxTrainable)->toBe(12)
        ->and($unit->cost)->toBe(['wood' => 120, 'clay' => 100, 'iron' => 150, 'crop' => 30])
        ->and($unit->cropUpkeep)->toBe(1)
        ->and($unit->durationSeconds)->toBe(1050)
        ->and($page->queue[0]->quantity)->toBe(5)
        ->and($page->queue[0]->remainingSeconds)->toBe(245);
});

test('academy parser reads actions, requirements, resource errors and the single research lane', function () {
    $html = <<<'HTML'
    <div class="researches">
      <div class="research">
        <img class="unit u4">
        <div class="resourceWrapper charges"><span class="resource"><i class="r1Big"></i><span class="value">1.000</span></span></div>
        <div class="duration"><span class="value">2:06:00</span></div>
        <div class="requirements"><span><a onclick="Manual.open('building', 20)">Stable <span title="current 1">level 1</span></a></span></div>
        <div class="cta"><button onclick="window.location.href='/build.php?id=38&amp;gid=22&amp;action=research&amp;t=t4&amp;checksum=abc'">Research</button></div>
      </div>
      <div class="research"><img class="unit u5"><div class="cta"><div class="errorMessage">Not enough resources</div></div></div>
    </div>
    <table class="under_progress"><tbody><tr><td><img class="unit u4"></td><td><span class="timer" value="300">0:05:00</span></td></tr></tbody></table>
    HTML;

    $page = app(AcademyPageParser::class)->parse($html);
    $units = collect($page->units)->keyBy('unitId');

    expect($units->get(4)->actionUri)->toContain('action=research')
        ->and($units->get(4)->cost['wood'])->toBe(1000)
        ->and($units->get(4)->durationSeconds)->toBe(7560)
        ->and($units->get(4)->requirements[0])->toMatchArray(['gid' => 20, 'required_level' => 1, 'met' => true])
        ->and($units->get(5)->serverMessage)->toBe('Not enough resources')
        ->and($page->queue[0]->unitId)->toBe(4)
        ->and($page->queue[0]->remainingSeconds)->toBe(300);
});

test('smithy parser reads current levels, resource shortages and queued target levels', function () {
    $html = <<<'HTML'
    <div class="researches"><div class="research"><img class="unit u1"><div class="title">Legionnaire <span class="level">level 2</span></div><div class="resourceWrapper charges"><span class="resource transfer fillUp"><i class="r1Big"></i><span class="value">900</span></span></div><div class="cta"><div class="errorMessage">Resources later</div></div></div></div>
    <table class="under_progress"><tbody><tr><td><img class="unit u1"></td><td><span class="level">level 3</span></td><td><span class="timer" value="600">0:10:00</span></td></tr></tbody></table>
    HTML;

    $page = app(SmithyPageParser::class)->parse($html);

    expect($page->units[0]->currentLevel)->toBe(2)
        ->and($page->units[0]->hasResourceShortage)->toBeTrue()
        ->and($page->units[0]->actionUri)->toBeNull()
        ->and($page->queue[0]->unitId)->toBe(1)
        ->and($page->queue[0]->targetLevel)->toBe(3);
});

test('provided Travian captures remain compatible with the structural parsers', function () {
    $capturesRoot = base_path('may-help/travian-samples/task17');

    if (! is_dir($capturesRoot)) {
        $this->markTestSkipped('The local task17 Travian captures are not available.');
    }

    $trainingParser = app(TrainingBuildingPageParser::class);
    $barracks = $trainingParser->parse((string) file_get_contents($capturesRoot.'/barracks/samp1.md'));
    $stable = $trainingParser->parse((string) file_get_contents($capturesRoot.'/Stable/samp01.md'));
    $academy = app(AcademyPageParser::class)->parse((string) file_get_contents($capturesRoot.'/Academy/samp1.md'));
    $smithy = app(SmithyPageParser::class)->parse((string) file_get_contents($capturesRoot.'/Smithy/samp01.md'));
    $newBarracks = $trainingParser->parse((string) file_get_contents($capturesRoot.'/barracks/new-samp01.md'));
    $newSmithy = app(SmithyPageParser::class)->parse((string) file_get_contents($capturesRoot.'/Smithy/new-samp01.md'));
    $newBarracksUnits = collect($newBarracks->units)->keyBy('unitId');
    $newSmithyUnits = collect($newSmithy->units)->keyBy('unitId');

    expect($barracks->units)->not->toBeEmpty()
        ->and($barracks->queue)->not->toBeEmpty()
        ->and($stable->units)->not->toBeEmpty()
        ->and($academy->units)->not->toBeEmpty()
        ->and($smithy->units)->not->toBeEmpty()
        ->and($newBarracksUnits->get(1)->smithyLevel)->toBe(0)
        ->and($newBarracksUnits->has(2))->toBeTrue()
        ->and($newSmithyUnits->get(1)->currentLevel)->toBe(1)
        ->and($newSmithyUnits->get(1)->hasResourceShortage)->toBeTrue()
        ->and($newSmithyUnits->get(4)->hasResourceShortage)->toBeFalse();
});
