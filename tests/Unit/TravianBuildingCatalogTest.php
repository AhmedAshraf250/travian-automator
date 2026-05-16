<?php

use App\Application\Travian\TravianBuildingCatalog;

test('travian building catalog distinguishes field and building queue kinds correctly', function () {
    expect(TravianBuildingCatalog::isFieldGid(1))->toBeTrue();
    expect(TravianBuildingCatalog::isFieldGid(15))->toBeFalse();
    expect(TravianBuildingCatalog::queueKindForName('الحطاب'))->toBe('field');
    expect(TravianBuildingCatalog::queueKindForName('المبنى الرئيسي'))->toBe('building');
});
