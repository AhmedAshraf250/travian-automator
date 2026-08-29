<?php

use Illuminate\Support\Facades\Artisan;

test('runtime opens the dashboard only when explicitly requested', function () {
    $definition = Artisan::all()['travian:runtime']->getDefinition();

    expect($definition->hasOption('open'))->toBeTrue()
        ->and($definition->getOption('open')->getDefault())->toBeFalse()
        ->and($definition->hasOption('no-open'))->toBeFalse();
});
