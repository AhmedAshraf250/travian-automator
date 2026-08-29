<?php

namespace App\Application\Accounts\Session;

use App\Application\Accounts\Rewards\ObservedDailyQuestRewardReaction;
use App\Application\Accounts\Rewards\ObservedQuestRewardReaction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Contracts\AccountSessionTransportFactory;
use App\Models\Account;

/**
 * Adds application response handling around an isolated transport session.
 */
class ObservedAccountSessionFactory implements AccountSessionFactory
{
    public function __construct(
        protected AccountSessionTransportFactory $transportFactory,
        protected TravianSessionResponseObserver $responseObserver,
        protected ObservedQuestRewardReaction $questRewardReaction,
        protected ObservedDailyQuestRewardReaction $dailyQuestRewardReaction,
    ) {}

    public function for(Account $account): AccountSession
    {
        $observedSession = new ObservedAccountSession(
            account: $account,
            inner: $this->transportFactory->for($account),
            observer: $this->responseObserver,
        );

        return new RewardReactiveAccountSession(
            account: $account,
            inner: $observedSession,
            questRewardReaction: $this->questRewardReaction,
            dailyQuestRewardReaction: $this->dailyQuestRewardReaction,
        );
    }
}
