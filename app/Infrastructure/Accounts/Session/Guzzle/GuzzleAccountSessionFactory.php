<?php

namespace App\Infrastructure\Accounts\Session\Guzzle;

use App\Application\Accounts\Rewards\ExecuteDailyQuestRewardCollection;
use App\Application\Accounts\Rewards\ExecuteQuestRewardCollection;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\ObservedAccountSession;
use App\Application\Accounts\Session\TravianSessionResponseObserver;
use App\Models\Account;
use App\Models\AccountProxy;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use RuntimeException;

/**
 * Creates Guzzle-backed isolated sessions for Travian accounts.
 */
class GuzzleAccountSessionFactory implements AccountSessionFactory
{
    /**
     * Create a new session factory.
     */
    public function __construct(
        protected TravianSessionResponseObserver $responseObserver,
        protected ExecuteQuestRewardCollection $questRewardCollection,
        protected ExecuteDailyQuestRewardCollection $dailyQuestRewardCollection,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function for(Account $account): AccountSession
    {
        $account->loadMissing('activeProxy');

        $currentTransportFingerprint = $account->currentTransportFingerprint();
        $canReusePersistedCookies = ! config('travian.transport.force_relogin_on_change', true)
            || $account->session_transport_fingerprint === null
            || hash_equals($account->session_transport_fingerprint, $currentTransportFingerprint);

        $cookieJar = CookieJar::fromArray(
            $this->mapCookiesByName($canReusePersistedCookies ? ($account->session_cookies ?? []) : []),
            parse_url($account->server_url, PHP_URL_HOST) ?: '',
        );

        $session = new GuzzleAccountSession(
            account: $account,
            client: new Client($this->buildOptions($account)),
            cookieJar: $cookieJar,
            transportFingerprint: $currentTransportFingerprint,
        );

        return new ObservedAccountSession($account, $session, $this->responseObserver, $this->questRewardCollection, $this->dailyQuestRewardCollection);
    }

    /**
     * Build the immutable client options for the account.
     *
     * @return array<string, mixed>
     */
    protected function buildOptions(Account $account): array
    {
        $headers = [];
        $effectiveUserAgent = $account->effectiveUserAgent();

        if ($effectiveUserAgent !== null && $effectiveUserAgent !== '') {
            $headers['User-Agent'] = $effectiveUserAgent;
        }

        $headers['Accept-Language'] = (string) config('travian.client.accept_language', 'en-US,en;q=0.9');
        $headers['Accept-Encoding'] = (string) config('travian.client.accept_encoding', 'gzip, deflate');

        return array_filter([
            'base_uri' => rtrim($account->server_url, '/').'/',
            'headers' => $headers,
            'timeout' => config('travian.http.timeout', 20),
            'connect_timeout' => config('travian.http.connect_timeout', 10),
            'verify' => $this->buildVerifyOption(),
            'proxy' => $this->buildProxyUri($account),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * Resolve the SSL verification setting for the isolated client.
     */
    protected function buildVerifyOption(): bool|string
    {
        $verifyOption = (bool) config('travian.http.verify', true);
        $caBundle = config('travian.http.ca_bundle');

        if (! is_string($caBundle) || trim($caBundle) === '') {
            return $verifyOption;
        }

        $normalizedPath = trim($caBundle);

        if (! is_file($normalizedPath)) {
            throw new RuntimeException("TRAVIAN_HTTP_CA_BUNDLE points to a non-existent file: {$normalizedPath}");
        }

        return $normalizedPath;
    }

    /**
     * Convert persisted cookie rows into a CookieJar::fromArray payload.
     *
     * @param  array<int, array<string, mixed>>  $cookies
     * @return array<string, string>
     */
    protected function mapCookiesByName(array $cookies): array
    {
        $mappedCookies = [];

        foreach ($cookies as $cookie) {
            if (! isset($cookie['Name'], $cookie['Value'])) {
                continue;
            }

            $mappedCookies[(string) $cookie['Name']] = (string) $cookie['Value'];
        }

        return $mappedCookies;
    }

    /**
     * Build the proxy URI for the account when configured.
     */
    protected function buildProxyUri(Account $account): ?string
    {
        $activeProxy = $account->activeProxy;

        if ($activeProxy instanceof AccountProxy && $activeProxy->isAvailable()) {
            return $this->buildProxyUriFromParts(
                scheme: $activeProxy->curlScheme(),
                host: $activeProxy->host,
                port: (int) $activeProxy->port,
                username: $activeProxy->username,
                password: $activeProxy->password,
            );
        }

        if ($account->proxy_ip === null || $account->proxy_port === null) {
            return null;
        }

        $scheme = $this->normalizeCurlProxyScheme((string) $account->proxy_scheme);

        return $this->buildProxyUriFromParts(
            scheme: $scheme,
            host: $account->proxy_ip,
            port: (int) $account->proxy_port,
            username: $account->proxy_username,
            password: $account->proxy_password,
        );
    }

    protected function normalizeCurlProxyScheme(string $scheme): string
    {
        return match ($scheme) {
            'https' => 'https',
            'socks4' => 'socks4a',
            'socks4a' => 'socks4a',
            'socks5' => 'socks5h',
            'socks5h' => 'socks5h',
            default => 'http',
        };
    }

    protected function buildProxyUriFromParts(string $scheme, string $host, int $port, ?string $username, ?string $password): string
    {
        $credentials = $username !== null && trim($username) !== ''
            ? rawurlencode($username).':'.rawurlencode((string) $password).'@'
            : '';

        return "{$scheme}://{$credentials}{$host}:{$port}";
    }
}
