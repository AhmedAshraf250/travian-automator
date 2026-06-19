<?php

use App\Infrastructure\Accounts\Session\Guzzle\GuzzleAccountSessionFactory;
use App\Models\Account;

test('guzzle account session factory builds proxy uri with the stored protocol', function () {
    $account = Account::factory()->make([
        'proxy_scheme' => 'socks5h',
        'proxy_ip' => '127.0.0.1',
        'proxy_port' => 1080,
        'proxy_username' => 'proxy-user',
        'proxy_password' => 'proxy-pass',
    ]);

    $method = new ReflectionMethod(GuzzleAccountSessionFactory::class, 'buildProxyUri');
    $method->setAccessible(true);

    $proxyUri = $method->invoke(app(GuzzleAccountSessionFactory::class), $account);

    expect($proxyUri)->toBe('socks5h://proxy-user:proxy-pass@127.0.0.1:1080');
});
