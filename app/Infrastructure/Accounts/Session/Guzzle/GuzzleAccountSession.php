<?php

namespace App\Infrastructure\Accounts\Session\Guzzle;

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Models\Account;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\TransferStats;

/**
 * Guzzle-backed isolated HTTP session for a single Travian account.
 */
class GuzzleAccountSession implements AccountSession
{
    /**
     * Create a new isolated Guzzle-backed session.
     */
    public function __construct(
        protected Account $account,
        protected Client $client,
        protected CookieJar $cookieJar,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function get(string $uri, array $options = []): SessionResponse
    {
        return $this->request('GET', $uri, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
    {
        return $this->request('POST', $uri, array_merge($options, [
            'form_params' => $formParams,
        ]));
    }

    /**
     * {@inheritDoc}
     */
    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
    {
        return $this->request('POST', $uri, array_merge($options, [
            'json' => $payload,
        ]));
    }

    /**
     * {@inheritDoc}
     */
    public function persist(): void
    {
        $this->account->forceFill([
            'session_cookies' => array_values($this->cookieJar->toArray()),
        ])->save();
    }

    /**
     * Perform a request inside the isolated session.
     *
     * @param  array<string, mixed>  $options
     */
    protected function request(string $method, string $uri, array $options = []): SessionResponse
    {
        $effectiveUri = $this->buildAbsoluteUri($uri);

        $response = $this->client->request($method, $uri, array_merge([
            'cookies' => $this->cookieJar,
            'http_errors' => false,
            'allow_redirects' => true,
            'on_stats' => function (TransferStats $stats) use (&$effectiveUri): void {
                $effectiveUri = (string) $stats->getEffectiveUri();
            },
        ], $options));

        /** @var array<string, list<string>> $headers */
        $headers = [];

        foreach ($response->getHeaders() as $headerName => $headerValues) {
            $headers[$headerName] = array_values($headerValues);
        }

        return new SessionResponse(
            statusCode: $response->getStatusCode(),
            body: (string) $response->getBody(),
            effectiveUri: $effectiveUri,
            headers: $headers,
        );
    }

    /**
     * Build an absolute URI from the account server base.
     */
    protected function buildAbsoluteUri(string $uri): string
    {
        if (preg_match('/^https?:\/\//i', $uri) === 1) {
            return $uri;
        }

        return rtrim($this->account->server_url, '/').'/'.ltrim($uri, '/');
    }
}
