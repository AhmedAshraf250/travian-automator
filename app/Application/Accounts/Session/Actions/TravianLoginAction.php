<?php

namespace App\Application\Accounts\Session\Actions;

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Exceptions\AuthenticationFailedException;
use App\Models\Account;
use JsonException;

/**
 * Establishes an authenticated Travian session for a single account.
 */
class TravianLoginAction
{
    /**
     * Ensure the provided session is authenticated for the given account.
     */
    public function handle(Account $account, AccountSession $session): void
    {
        $landingPage = $session->get('/');

        if ($this->isAuthenticatedHtml($landingPage->body)) {
            $session->persist();

            return;
        }

        $loginResponse = $session->postJson(
            '/api/v1/auth/login',
            $this->buildApiLoginPayload($account),
            $this->buildApiLoginOptions($landingPage->body),
        );

        if (! $loginResponse->successful()) {
            throw new AuthenticationFailedException('Travian login API rejected the provided credentials or request context.');
        }

        $authRedirectUri = $this->extractAuthRedirectUri($loginResponse->body);
        $authResponse = $session->get($authRedirectUri);

        if (! $this->isAuthenticatedHtml($authResponse->body)) {
            $overviewResponse = $session->get('/dorf1.php');

            if (! $this->isAuthenticatedHtml($overviewResponse->body)) {
                throw new AuthenticationFailedException('Travian login completed, but the session did not reach an authenticated game page.');
            }
        }

        $session->persist();
    }

    /**
     * Determine whether the HTML belongs to an authenticated game page.
     */
    public function isAuthenticatedHtml(string $html): bool
    {
        return str_contains($html, 'class="villageList"')
            || str_contains($html, 'id="sidebarBoxVillageList"')
            || str_contains($html, 'body class="village1')
            || str_contains($html, 'body class="village2');
    }

    /**
     * Build the modern Travian login payload.
     *
     * @return array{name:string,password:string,w:string,mobileOptimizations:bool}
     */
    protected function buildApiLoginPayload(Account $account): array
    {
        return [
            'name' => $account->username,
            'password' => $account->password,
            'w' => '1920:1200',
            'mobileOptimizations' => false,
        ];
    }

    /**
     * Build the request options expected by the Travian login API.
     *
     * @return array<string, mixed>
     */
    protected function buildApiLoginOptions(string $landingHtml): array
    {
        return [
            'headers' => [
                'accept' => 'application/json, text/javascript, */*; q=0.01',
                'content-type' => 'application/json; charset=UTF-8',
                'x-requested-with' => 'XMLHttpRequest',
                'x-version' => $this->extractClientVersion($landingHtml),
            ],
        ];
    }

    /**
     * Extract the Travian client bundle version used by the login request.
     */
    protected function extractClientVersion(string $html): string
    {
        if (preg_match('/gpack\/([0-9.]+)\//', $html, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/Variables\.js\?([0-9.]+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '417.8';
    }

    /**
     * Extract the auth redirect URI from the login API response body.
     */
    protected function extractAuthRedirectUri(string $body): string
    {
        $decoded = $this->decodeJson($body);

        $uri = $this->findRedirectUriInDecodedResponse($decoded);

        if ($uri !== null) {
            return $uri;
        }

        throw new AuthenticationFailedException('Travian login API response did not expose an auth redirect URI.');
    }

    /**
     * Decode a JSON response body into an associative array.
     *
     * @return array<string, mixed>
     */
    protected function decodeJson(string $body): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (JsonException $exception) {
            throw new AuthenticationFailedException('Travian login API returned malformed JSON.', previous: $exception);
        }
    }

    /**
     * Search the decoded login response for a follow-up auth URI.
     *
     * @param  array<string, mixed>  $decoded
     */
    protected function findRedirectUriInDecodedResponse(array $decoded): ?string
    {
        $candidateKeys = ['redirectUrl', 'redirectUri', 'url', 'href', 'location'];

        foreach ($candidateKeys as $candidateKey) {
            $candidate = $decoded[$candidateKey] ?? null;

            if (is_string($candidate) && str_contains($candidate, '/api/v1/auth')) {
                return $candidate;
            }
        }

        $code = $decoded['code'] ?? null;

        if (is_string($code) && $code !== '') {
            return '/api/v1/auth?code='.rawurlencode($code).'&response_type=redirect';
        }

        $serializedResponse = json_encode($decoded);

        if (is_string($serializedResponse) && preg_match('/(\/api\/v1\/auth\?code=[^"]+response_type=redirect)/', $serializedResponse, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES);
        }

        return null;
    }
}
