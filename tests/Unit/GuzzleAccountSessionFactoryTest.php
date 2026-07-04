<?php

use App\Infrastructure\Accounts\Session\Guzzle\GuzzleAccountSessionFactory;
use App\Models\Account;
use App\Models\AccountProxy;

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

test('guzzle account session factory resolves socks proxies through the proxy dns variant', function () {
    $account = Account::factory()->make([
        'proxy_scheme' => 'socks5',
        'proxy_ip' => '127.0.0.1',
        'proxy_port' => 1080,
    ]);

    $method = new ReflectionMethod(GuzzleAccountSessionFactory::class, 'buildProxyUri');
    $method->setAccessible(true);

    $proxyUri = $method->invoke(app(GuzzleAccountSessionFactory::class), $account);

    expect($proxyUri)->toBe('socks5h://127.0.0.1:1080');
});

test('guzzle account session factory prefers the active proxy pool entry', function () {
    $account = Account::factory()->make([
        'proxy_scheme' => 'http',
        'proxy_ip' => '127.0.0.1',
        'proxy_port' => 8080,
    ]);
    $proxy = new AccountProxy([
        'scheme' => 'socks5',
        'host' => '10.0.0.1',
        'port' => 1081,
        'username' => 'pool-user',
        'password' => 'pool-pass',
    ]);

    $account->setRelation('activeProxy', $proxy);

    $method = new ReflectionMethod(GuzzleAccountSessionFactory::class, 'buildProxyUri');
    $method->setAccessible(true);

    $proxyUri = $method->invoke(app(GuzzleAccountSessionFactory::class), $account);

    expect($proxyUri)->toBe('socks5h://pool-user:pool-pass@10.0.0.1:1081');
});
