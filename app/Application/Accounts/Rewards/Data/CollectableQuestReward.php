<?php

namespace App\Application\Accounts\Rewards\Data;

/**
 * Represents one progressive task reward that Travian says can be collected.
 */
final readonly class CollectableQuestReward
{
    /**
     * @param  array<string, int>  $rewardValues
     */
    public function __construct(
        public string $taskName,
        public string $questType,
        public string $scope,
        public int $targetLevel,
        public ?int $heroLevel,
        public ?int $buildingId,
        public string $questId,
        public string $group,
        public string $levelTitle,
        public array $rewardValues,
    ) {}

    /**
     * Build the JSON payload expected by Travian's collect reward endpoint.
     *
     * @return array<string, int|string>
     */
    public function collectionPayload(): array
    {
        $payload = [
            'questType' => $this->questType,
            'scope' => $this->scope,
            'targetLevel' => $this->targetLevel,
        ];

        if ($this->heroLevel !== null) {
            $payload['heroLevel'] = $this->heroLevel;
        }

        if ($this->buildingId !== null) {
            $payload['buildingId'] = $this->buildingId;
        }

        return $payload;
    }
}
