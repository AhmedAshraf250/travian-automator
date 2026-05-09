<?php

namespace App\Application\Accounts\Session\Contracts;

use App\Application\Accounts\Session\Data\SessionResponse;

/**
 * Describes an isolated HTTP session dedicated to a single Travian account.
 */
interface AccountSession
{
    /**
     * Send a GET request within the account session.
     *
     * @param  array<string, mixed>  $options
     */
    public function get(string $uri, array $options = []): SessionResponse;

    /**
     * Submit a classic form request within the account session.
     *
     * @param  array<string, mixed>  $formParams
     * @param  array<string, mixed>  $options
     */
    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse;

    /**
     * Submit a JSON request within the account session.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function postJson(string $uri, array $payload, array $options = []): SessionResponse;

    /**
     * Persist the in-memory session state back to storage.
     */
    public function persist(): void;
}
