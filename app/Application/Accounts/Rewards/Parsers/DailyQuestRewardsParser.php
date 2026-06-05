<?php

namespace App\Application\Accounts\Rewards\Parsers;

use App\Application\Accounts\Rewards\Data\CollectableDailyQuestReward;
use JsonException;

/**
 * Reads Travian daily quest responses and extracts account-level rewards.
 */
class DailyQuestRewardsParser
{
    /**
     * Determine whether a page document shows the daily quest reward alert.
     */
    public function hasCollectableRewardIndicator(string $html): bool
    {
        if (preg_match_all('/<a\b(?=[^>]*class=["\'][^"\']*\bdailyQuests\b[^"\']*["\'])[\s\S]*?<\/a>/u', $html, $matches) !== false) {
            foreach ($matches[0] as $dailyQuestLink) {
                if ($this->containsDailyQuestIndicator($dailyQuestLink)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract the last seen timestamp from the first daily quest GraphQL response.
     */
    public function parseLastSeenAt(string $contents): ?int
    {
        $payload = $this->decodePayload($contents);
        $lastSeenAt = $payload['data']['ownPlayer']['dailyQuests']['lastSeenAt'] ?? null;

        return is_numeric($lastSeenAt) ? (int) $lastSeenAt : null;
    }

    /**
     * Extract rewards that have enough achieved points and are not redeemed yet.
     *
     * @return list<CollectableDailyQuestReward>
     */
    public function parseCollectableRewards(string $contents): array
    {
        $payload = $this->decodePayload($contents);
        $dailyQuests = $payload['data']['ownPlayer']['dailyQuests'] ?? null;

        if (! is_array($dailyQuests)) {
            return [];
        }

        $achievedPoints = $dailyQuests['achievedPoints'] ?? null;
        $rewards = $dailyQuests['rewards'] ?? null;

        if (! is_numeric($achievedPoints) || ! is_array($rewards)) {
            return [];
        }

        $collectableRewards = [];

        foreach ($rewards as $reward) {
            if (! is_array($reward)) {
                continue;
            }

            $collectableReward = $this->collectableRewardFromPayload($reward, (int) $achievedPoints);

            if ($collectableReward instanceof CollectableDailyQuestReward) {
                $collectableRewards[] = $collectableReward;
            }
        }

        usort(
            $collectableRewards,
            static fn (CollectableDailyQuestReward $left, CollectableDailyQuestReward $right): int => $left->requiredPoints <=> $right->requiredPoints,
        );

        return array_values($collectableRewards);
    }

    protected function containsDailyQuestIndicator(string $dailyQuestMarkup): bool
    {
        if (preg_match('/<div\b[^>]*class=["\'][^"\']*\bindicator\b[^"\']*["\'][^>]*>\s*!\s*<\/div>/u', $dailyQuestMarkup) === 1) {
            return true;
        }

        return str_contains($dailyQuestMarkup, 'rewardAvailable');
    }

    /**
     * @param  array<string, mixed>  $reward
     */
    protected function collectableRewardFromPayload(array $reward, int $achievedPoints): ?CollectableDailyQuestReward
    {
        $rewardId = $reward['id'] ?? null;
        $requiredPoints = $reward['points'] ?? null;
        $awardRedeemed = $reward['awardRedeemed'] ?? null;

        if (! is_string($rewardId) || $rewardId === '' || ! is_numeric($requiredPoints)) {
            return null;
        }

        if ($awardRedeemed !== false || (int) $requiredPoints > $achievedPoints) {
            return null;
        }

        return new CollectableDailyQuestReward(
            rewardId: $rewardId,
            requiredPoints: (int) $requiredPoints,
            achievedPoints: $achievedPoints,
            awardDescription: (string) ($reward['awardDescription'] ?? ''),
            possibleAwards: $this->normalizePossibleAwards($reward['possibleAwards'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodePayload(string $contents): array
    {
        $candidate = trim($this->extractCopiedResponse($contents));

        try {
            $decoded = json_decode($candidate, true, flags: JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }

    protected function extractCopiedResponse(string $contents): string
    {
        $markerPosition = mb_stripos($contents, '# Copy Response:');

        if ($markerPosition === false) {
            return $contents;
        }

        $response = mb_substr($contents, $markerPosition + mb_strlen('# Copy Response:'));
        $response = preg_replace('/^\s*# --------------#\s*/u', '', $response) ?? $response;
        $nextSectionPosition = mb_stripos($response, '# --------------#');

        if ($nextSectionPosition === false) {
            return $response;
        }

        return mb_substr($response, 0, $nextSectionPosition);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function normalizePossibleAwards(mixed $possibleAwards): array
    {
        if (! is_array($possibleAwards)) {
            return [];
        }

        return array_values(array_filter(
            $possibleAwards,
            static fn (mixed $possibleAward): bool => is_array($possibleAward),
        ));
    }
}
