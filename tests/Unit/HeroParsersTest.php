<?php

use App\Application\Accounts\Hero\Parsers\HeroAdventurePageAnalyzer;
use App\Application\Accounts\Hero\Parsers\HeroAttributesAnalyzer;
use App\Application\Accounts\Hero\Parsers\HeroTopBarParser;

function heroFixture(string $path): string
{
    return file_get_contents(base_path('tests/Fixtures/travian-samples/task05/'.$path));
}

function heroCopiedResponseFixture(string $path): string
{
    $contents = heroFixture($path);
    $marker = '# copy response:';
    $position = strpos($contents, $marker);

    if ($position === false) {
        return $contents;
    }

    return trim(substr($contents, $position + strlen($marker)));
}

test('hero top bar parser extracts alive hero state from dorf1', function () {
    $state = app(HeroTopBarParser::class)
        ->parse(heroFixture('standard-dorf1-response.md'));

    expect($state)->not->toBeNull()
        ->and($state->status)->toBe('home')
        ->and($state->healthPercent)->toBe(95.0)
        ->and($state->experiencePercent)->toBe(48)
        ->and($state->hasUnspentAttributePoints)->toBeTrue()
        ->and($state->adventuresAvailableCount)->toBe(2);
});

test('hero top bar parser extracts dead hero state', function () {
    $state = app(HeroTopBarParser::class)
        ->parse(heroFixture('dead-hero/standard-dorf1-response.md'));

    expect($state)->not->toBeNull()
        ->and($state->status)->toBe('dead')
        ->and($state->healthPercent)->toBe(0.0);
});

test('hero adventure analyzer extracts adventures in page order', function () {
    $analysis = app(HeroAdventurePageAnalyzer::class)
        ->analyze(heroFixture('step01-press-adventure-button-response.md'));

    expect($analysis->adventures)->toHaveCount(2)
        ->and($analysis->adventures[0]->number)->toBe(55)
        ->and($analysis->adventures[0]->place)->toBe('clay')
        ->and($analysis->adventures[0]->difficulty)->toBe(2)
        ->and($analysis->adventures[0]->travelingDuration)->toBe(2622)
        ->and($analysis->adventures[1]->number)->toBe(56)
        ->and($analysis->heroState?->status)->toBe('home');
});

test('hero attributes analyzer extracts revive cost and duration', function () {
    $analysis = app(HeroAttributesAnalyzer::class)
        ->analyze(heroFixture('dead-hero/step02-choose-attributes-response.md'));

    expect($analysis)->not->toBeNull()
        ->and($analysis->heroState->status)->toBe('dead')
        ->and($analysis->reviveRequiredResources)->toMatchArray([
            'wood' => 810,
            'clay' => 590,
            'iron' => 520,
            'crop' => 340,
            'crop_consumption' => 6,
        ])
        ->and($analysis->reviveDurationSeconds)->toBe(14400)
        ->and($analysis->reviveDurationLabel)->toBe('4:00:00');
});

test('hero attributes analyzer allows revive when crop is negative but Travian shows no charges error', function () {
    $payload = json_decode(heroFixture('dead-hero/step02-choose-attributes-response.md'), true, flags: JSON_THROW_ON_ERROR);
    $payload['revive']['activeVillage']['enoughFreeCrop'] = false;
    $payload['revive']['activeVillage']['negativeCrop'] = true;
    $payload['revive']['chargesErrorMessage'] = null;

    $analysis = app(HeroAttributesAnalyzer::class)
        ->analyze(json_encode($payload, JSON_THROW_ON_ERROR));

    expect($analysis)->not->toBeNull()
        ->and($analysis->canReviveWithResources)->toBeTrue();
});

test('hero attributes analyzer builds weighted distribution payload', function () {
    $payload = app(HeroAttributesAnalyzer::class)->buildDistributionPayload([
        'power' => 1,
        'offBonus' => 0,
        'defBonus' => 1,
        'productionPoints' => 2,
    ], 4);

    expect($payload)->toBe([
        'power' => 1,
        'offBonus' => 0,
        'defBonus' => 1,
        'productionPoints' => 2,
    ]);
});
