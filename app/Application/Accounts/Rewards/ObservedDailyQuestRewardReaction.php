<?php

namespace App\Application\Accounts\Rewards;

use App\Application\Accounts\Rewards\Parsers\DailyQuestRewardsParser;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Models\Account;

/**
 * Turns an eligible observed dorf1 response into a daily reward action.
 */
class ObservedDailyQuestRewardReaction
{
    public function __construct(
        protected DailyQuestRewardsParser $dailyQuestRewardsParser,
        protected ExecuteDailyQuestRewardCollection $rewardCollection,
    ) {}

    public function handle(Account $account, AccountSession $session, SessionResponse $response): void
    {
        if (! $response->successful()) {
            return;
        }

        $path = (string) (parse_url($response->effectiveUri, PHP_URL_PATH) ?: '');

        if (str_contains($path, '/dorf1.php') && $this->dailyQuestRewardsParser->hasCollectableRewardIndicator($response->body)) {
            $this->rewardCollection->handle($account, $session, $response);
        }
    }
}
