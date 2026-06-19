<?php

use App\Application\Accounts\Import\BulkAccountImportParser;

test('parser normalizes multiple account rows', function () {
    $parser = app(BulkAccountImportParser::class);

    $records = $parser->parse(implode(PHP_EOL, [
        '!https://ts7.x1.arabics.travian.com!marshal!12345678!127.0.0.1:8080!Mozilla/5.0',
        '!https://example.com/!agent!password!',
    ]));

    expect($records)->toHaveCount(2);
    expect($records[0]->serverUrl)->toBe('https://ts7.x1.arabics.travian.com/');
    expect($records[0]->proxyScheme)->toBe('http');
    expect($records[0]->proxyIp)->toBe('127.0.0.1');
    expect($records[0]->proxyPort)->toBe(8080);
    expect($records[0]->userAgent)->toBe('Mozilla/5.0');
    expect($records[1]->serverUrl)->toBe('https://example.com/');
    expect($records[1]->proxyIp)->toBeNull();
    expect($records[1]->userAgent)->toBeNull();
});

test('parser accepts proxy urls with socks protocols and credentials', function () {
    $parser = app(BulkAccountImportParser::class);

    $records = $parser->parse(implode(PHP_EOL, [
        '!https://ts7.x1.arabics.travian.com!marshal!12345678!socks5://proxy-user:proxy-pass@127.0.0.1:1080!Mozilla/5.0',
        'https://ts7.x1.arabics.travian.com marshal2 12345678 socks5h://10.0.0.1:1081 Mozilla/5.0',
    ]));

    expect($records)->toHaveCount(2);
    expect($records[0]->proxyScheme)->toBe('socks5');
    expect($records[0]->proxyIp)->toBe('127.0.0.1');
    expect($records[0]->proxyPort)->toBe(1080);
    expect($records[0]->proxyUsername)->toBe('proxy-user');
    expect($records[0]->proxyPassword)->toBe('proxy-pass');
    expect($records[0]->userAgent)->toBe('Mozilla/5.0');
    expect($records[1]->proxyScheme)->toBe('socks5h');
    expect($records[1]->proxyIp)->toBe('10.0.0.1');
    expect($records[1]->proxyPort)->toBe(1081);
    expect($records[1]->userAgent)->toBe('Mozilla/5.0');
});

test('parser keeps legacy split proxy ip and port compatible', function () {
    $parser = app(BulkAccountImportParser::class);

    $records = $parser->parse('!https://ts7.x1.arabics.travian.com!marshal!12345678!127.0.0.1!8080!Mozilla/5.0');

    expect($records[0]->proxyScheme)->toBe('http');
    expect($records[0]->proxyIp)->toBe('127.0.0.1');
    expect($records[0]->proxyPort)->toBe(8080);
    expect($records[0]->userAgent)->toBe('Mozilla/5.0');
});
