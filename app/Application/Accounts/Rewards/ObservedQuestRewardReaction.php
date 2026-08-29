<?php

namespace App\Application\Accounts\Rewards;

use App\Application\Accounts\Rewards\Parsers\QuestRewardsParser;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Sync\Parsers\ActiveVillageIdParser;
use App\Models\Account;
use App\Models\Village;

/**
 * Turns an eligible observed dorf1 response into a progressive reward action.
 */
class ObservedQuestRewardReaction
{
    public function __construct(
        protected QuestRewardsParser $questRewardsParser,
        protected ActiveVillageIdParser $activeVillageIdParser,
        protected ExecuteQuestRewardCollection $rewardCollection,
    ) {}

    public function handle(Account $account, AccountSession $session, SessionResponse $response): void
    {
        if (! $response->successful()) {
            return;
        }

        $path = (string) (parse_url($response->effectiveUri, PHP_URL_PATH) ?: '');

        if (! str_contains($path, '/dorf1.php') || ! $this->questRewardsParser->hasCollectableRewardIndicator($response->body)) {
            return;
        }

        $travianVillageId = $this->activeVillageIdParser->parse($response->body);

        if ($travianVillageId === null) {
            return;
        }

        $village = $account->villages()->firstWhere('travian_village_id', $travianVillageId);

        if ($village instanceof Village) {
            $this->rewardCollection->handle($account, $village, $session, $response);
        }
    }
}
