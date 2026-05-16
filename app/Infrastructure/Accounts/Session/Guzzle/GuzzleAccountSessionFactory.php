<?php

namespace App\Infrastructure\Accounts\Session\Guzzle;

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Models\Account;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use RuntimeException;

/**
 * Creates Guzzle-backed isolated sessions for Travian accounts.
 */
class GuzzleAccountSessionFactory implements AccountSessionFactory
{
    /**
     * {@inheritDoc}
     */
    public function for(Account $account): AccountSession
    {
        $currentTransportFingerprint = $account->currentTransportFingerprint();
        $canReusePersistedCookies = ! config('travian.transport.force_relogin_on_change', true)
            || $account->session_transport_fingerprint === null
            || hash_equals($account->session_transport_fingerprint, $currentTransportFingerprint);

        $cookieJar = CookieJar::fromArray(
            $this->mapCookiesByName($canReusePersistedCookies ? ($account->session_cookies ?? []) : []),
            parse_url($account->server_url, PHP_URL_HOST) ?: '',
        );

        return new GuzzleAccountSession(
            account: $account,
            client: new Client($this->buildOptions($account)),
            cookieJar: $cookieJar,
            transportFingerprint: $currentTransportFingerprint,
        );
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
        if ($account->proxy_ip === null || $account->proxy_port === null) {
            return null;
        }

        $credentials = $account->proxy_username !== null
            ? rawurlencode($account->proxy_username).':'.rawurlencode((string) $account->proxy_password).'@'
            : '';

        return "http://{$credentials}{$account->proxy_ip}:{$account->proxy_port}";
    }
}
