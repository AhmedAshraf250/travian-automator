<?php

use App\Application\Accounts\Construction\BuildPageAnalyzer;

test('build page analyzer extracts resource shortage metadata', function () {
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/test03/building-resource-shortage.md'));

    $analysis = app(BuildPageAnalyzer::class)->analyze((string) $html);

    expect($analysis->hasAction())->toBeFalse()
        ->and($analysis->isResourceShortage())->toBeTrue()
        ->and($analysis->requiredResources)->toMatchArray([
            'wood' => 1735,
            'clay' => 990,
            'iron' => 1485,
            'crop' => 495,
            'crop_consumption' => 2,
        ])
        ->and($analysis->resourceReadySeconds)->toBe(1329)
        ->and($analysis->resourceReadyLabel)->toBe('0:22:09')
        ->and($analysis->blockedMessage)->toContain('ستتوفر الموارد اللازمة');
});

test('build page analyzer extracts first time rally point construction action', function () {
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/test04/construct-rally-point-first-time/step02-build-page-response.md'));

    $analysis = app(BuildPageAnalyzer::class)->analyze((string) $html, 16);

    expect($analysis->actionUri)->toBe('/dorf2.php?id=39&gid=16&action=build&checksum=5d1a0b')
        ->and($analysis->availableBuildings[16]['required_resources'] ?? [])->toMatchArray([
            'wood' => 110,
            'clay' => 160,
            'iron' => 90,
            'crop' => 70,
            'crop_consumption' => 1,
        ]);
});

test('build page analyzer extracts tribe wall construction action', function () {
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/test04/construct-wall-first-time/step02-build-page-response.md'));

    $analysis = app(BuildPageAnalyzer::class)->analyze((string) $html, 32);

    expect($analysis->actionUri)->toBe('/dorf2.php?id=40&gid=32&action=build&checksum=a574dd')
        ->and($analysis->availableBuildings[32]['name'] ?? null)->toBe('الحاجز الأرضي');
});

test('build page analyzer extracts construct tabs and blocked requirements', function () {
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/test04/construct-building/step02-build-page-response.md'));

    $townHallAnalysis = app(BuildPageAnalyzer::class)->analyze((string) $html, 24);
    $horseTroughAnalysis = app(BuildPageAnalyzer::class)->analyze((string) $html, 41);

    expect($townHallAnalysis->activeCategory)->toBe(1)
        ->and($townHallAnalysis->actionUri)->toBe('/dorf2.php?id=37&gid=24&action=build&checksum=09fb23')
        ->and($horseTroughAnalysis->actionUri)->toBeNull()
        ->and($horseTroughAnalysis->blockedReason)->toBe('missing_requirements')
        ->and($horseTroughAnalysis->missingRequirements)->toMatchArray([
            ['gid' => 20, 'name' => 'إسطبل', 'required_level' => 20, 'current_level' => 0],
            ['gid' => 16, 'name' => 'نقطة التجمع', 'required_level' => 10, 'current_level' => 1],
        ]);
});

test('build page analyzer extracts active military and resources categories', function () {
    $militaryHtml = file_get_contents(base_path('tests/Fixtures/travian-samples/test04/infrastructure-military-resources/step03-choose-militry-tab-response.md'));
    $resourcesHtml = file_get_contents(base_path('tests/Fixtures/travian-samples/test04/infrastructure-military-resources/step04-choose-resources-tab-response.md'));

    expect(app(BuildPageAnalyzer::class)->analyze((string) $militaryHtml)->activeCategory)->toBe(2)
        ->and(app(BuildPageAnalyzer::class)->analyze((string) $resourcesHtml)->activeCategory)->toBe(3);
});

test('build page analyzer extracts existing building upgrade action', function () {
    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/test04/upgrade-building-already-exist/step02-build-page-response.md'));

    $analysis = app(BuildPageAnalyzer::class)->analyze((string) $html);

    expect($analysis->actionUri)->toBe('/dorf2.php?id=32&gid=17&action=build&checksum=5d1a0b')
        ->and($analysis->requiredResources)->toMatchArray([
            'wood' => 100,
            'clay' => 90,
            'iron' => 155,
            'crop' => 90,
            'crop_consumption' => 2,
        ]);
});
