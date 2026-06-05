<?php

namespace App\Application\Accounts\Rewards\Data;

/**
 * Represents one account-level daily quest reward that can be collected.
 */
final readonly class CollectableDailyQuestReward
{
    /**
     * @param  list<array<string, mixed>>  $possibleAwards
     */
    public function __construct(
        public string $rewardId,
        public int $requiredPoints,
        public int $achievedPoints,
        public string $awardDescription,
        public array $possibleAwards,
    ) {}

    /**
     * Build the JSON payload expected by Travian's daily quest award endpoint.
     *
     * @return array{action: string, questId: string}
     */
    public function collectionPayload(): array
    {
        return [
            'action' => 'dailyQuest',
            'questId' => $this->rewardId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogPayload(): array
    {
        return [
            'reward_id' => $this->rewardId,
            'required_points' => $this->requiredPoints,
            'achieved_points' => $this->achievedPoints,
            'award_description' => $this->awardDescription,
            'possible_awards' => $this->possibleAwards,
            'request_payload' => $this->collectionPayload(),
        ];
    }
}
