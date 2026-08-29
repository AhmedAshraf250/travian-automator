<?php

namespace App\Application\Accounts\Session;

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Models\Account;

/**
 * Decorates an account session and persists useful data from responses already fetched.
 */
class ObservedAccountSession implements AccountSession
{
    /**
     * Create an observed session wrapper.
     */
    public function __construct(
        protected Account $account,
        protected AccountSession $inner,
        protected TravianSessionResponseObserver $observer,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function get(string $uri, array $options = []): SessionResponse
    {
        return $this->observe($this->inner->get($uri, $options));
    }

    /**
     * {@inheritDoc}
     */
    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
    {
        return $this->observe($this->inner->postForm($uri, $formParams, $options));
    }

    /**
     * {@inheritDoc}
     */
    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
    {
        return $this->observe($this->inner->postJson($uri, $payload, $options));
    }

    /**
     * {@inheritDoc}
     */
    public function putJson(string $uri, array $payload, array $options = []): SessionResponse
    {
        return $this->observe($this->inner->putJson($uri, $payload, $options));
    }

    /**
     * {@inheritDoc}
     */
    public function persist(): void
    {
        $this->inner->persist();
    }

    /**
     * Persist useful data from the response while returning it unchanged.
     */
    protected function observe(SessionResponse $response): SessionResponse
    {
        $this->observer->observe($this->account, $response);

        return $response;
    }
}
