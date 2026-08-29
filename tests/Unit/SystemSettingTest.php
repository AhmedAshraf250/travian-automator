<?php

use App\Models\SystemSetting;

test('construction fields default to wood and clay before iron and crop', function () {
    expect(SystemSetting::defaultFieldPriority())->toBe([
        'wood' => 1,
        'clay' => 1,
        'iron' => 2,
        'crop' => 2,
    ]);
});
