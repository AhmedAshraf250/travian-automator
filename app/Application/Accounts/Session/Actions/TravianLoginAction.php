<?php

namespace App\Application\Accounts\Session\Actions;

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Exceptions\AuthenticationFailedException;
use App\Application\Accounts\Session\Exceptions\LoginFormNotFoundException;
use App\Models\Account;
use DOMDocument;
use DOMElement;
use DOMXPath;

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

        [$action, $payload] = $this->extractLoginPayload($landingPage->body, $account);

        $loginResponse = $session->postForm($action, $payload);

        if (! $this->isAuthenticatedHtml($loginResponse->body)) {
            throw new AuthenticationFailedException('Travian login failed or returned an unexpected page.');
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
     * Build a login form payload from the HTML page.
     *
     * @return array{0:string,1:array<string,mixed>}
     */
    protected function extractLoginPayload(string $html, Account $account): array
    {
        if ($this->isReactLoginPage($html)) {
            throw new LoginFormNotFoundException(
                'Travian now renders the login scene with JavaScript. We need the actual login network request or endpoint to wire the authenticator to the current flow.',
            );
        }

        $xpath = $this->createXPath($html);
        $formNode = null;

        foreach ($xpath->query('//form') ?: [] as $candidateForm) {
            if (! $candidateForm instanceof DOMElement) {
                continue;
            }

            $passwordInputs = $xpath->query('.//input[@type="password"]', $candidateForm);

            if ($passwordInputs !== false && $passwordInputs->length > 0) {
                $formNode = $candidateForm;

                break;
            }
        }

        if (! $formNode instanceof DOMElement) {
            throw new LoginFormNotFoundException('Could not find a recognizable Travian login form.');
        }

        /** @var array<string, mixed> $payload */
        $payload = [];
        $usernameFieldName = null;
        $passwordFieldName = null;

        foreach ($xpath->query('.//input', $formNode) ?: [] as $inputElement) {
            if (! $inputElement instanceof DOMElement) {
                continue;
            }

            $name = trim((string) $inputElement->getAttribute('name'));

            if ($name === '') {
                continue;
            }

            $type = strtolower((string) $inputElement->getAttribute('type'));
            $value = (string) $inputElement->getAttribute('value');

            if (in_array($type, ['', 'hidden', 'submit'], true)) {
                $payload[$name] = $value;
            }

            if ($type === 'password') {
                $passwordFieldName = $name;
            }

            if (in_array($type, ['text', 'email'], true) && $usernameFieldName === null) {
                $usernameFieldName = $name;
            }
        }

        if ($usernameFieldName === null || $passwordFieldName === null) {
            throw new LoginFormNotFoundException('The Travian login form is missing username or password fields.');
        }

        $payload[$usernameFieldName] = $account->username;
        $payload[$passwordFieldName] = $account->password;

        $action = trim((string) $formNode->getAttribute('action'));

        return [$action === '' ? '/' : $action, $payload];
    }

    /**
     * Detect the modern React-based Travian login scene.
     */
    protected function isReactLoginPage(string $html): bool
    {
        return str_contains($html, 'window.Travian.React.Login.render(')
            || str_contains($html, 'id="loginScene"');
    }

    /**
     * Create an XPath instance for the provided HTML string.
     */
    protected function createXPath(string $html): DOMXPath
    {
        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }
}
