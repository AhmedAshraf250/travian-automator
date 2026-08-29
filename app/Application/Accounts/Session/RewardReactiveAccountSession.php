<?php

namespace App\Application\Accounts\Session;

use App\Application\Accounts\Rewards\ObservedDailyQuestRewardReaction;
use App\Application\Accounts\Rewards\ObservedQuestRewardReaction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Models\Account;

/**
 * Reacts to eligible observed responses without recursively reacting to its own requests.
 */
class RewardReactiveAccountSession implements AccountSession
{
    public function __construct(
        protected Account $account,
        protected AccountSession $inner,
        protected ObservedQuestRewardReaction $questRewardReaction,
        protected ObservedDailyQuestRewardReaction $dailyQuestRewardReaction,
    ) {}

    public function get(string $uri, array $options = []): SessionResponse
    {
        return $this->react($this->inner->get($uri, $options));
    }

    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
    {
        return $this->react($this->inner->postForm($uri, $formParams, $options));
    }

    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
    {
        return $this->react($this->inner->postJson($uri, $payload, $options));
    }

    public function putJson(string $uri, array $payload, array $options = []): SessionResponse
    {
        return $this->react($this->inner->putJson($uri, $payload, $options));
    }

    public function persist(): void
    {
        $this->inner->persist();
    }

    protected function react(SessionResponse $response): SessionResponse
    {
        if (! $this->isSameTravianHost($response->effectiveUri)) {
            return $response;
        }

        $this->questRewardReaction->handle($this->account, $this->inner, $response);
        $this->dailyQuestRewardReaction->handle($this->account, $this->inner, $response);

        return $response;
    }

    protected function isSameTravianHost(string $effectiveUri): bool
    {
        $accountHost = parse_url($this->account->server_url, PHP_URL_HOST);
        $responseHost = parse_url($effectiveUri, PHP_URL_HOST);

        return is_string($accountHost)
            && is_string($responseHost)
            && strcasecmp($accountHost, $responseHost) === 0;
    }
}
