<?php

use App\Application\Accounts\Import\BulkAccountImportParser;

test('parser normalizes multiple account rows', function () {
    $parser = app(BulkAccountImportParser::class);

    $records = $parser->parse(implode(PHP_EOL, [
        '!https://ts7.x1.arabics.travian.com!marshal!12345678!127.0.0.1!8080!Mozilla/5.0',
        '!https://example.com/!agent!password!',
    ]));

    expect($records)->toHaveCount(2);
    expect($records[0]->serverUrl)->toBe('https://ts7.x1.arabics.travian.com/');
    expect($records[0]->proxyPort)->toBe(8080);
    expect($records[1]->serverUrl)->toBe('https://example.com/');
    expect($records[1]->proxyIp)->toBeNull();
    expect($records[1]->userAgent)->toBeNull();
});
