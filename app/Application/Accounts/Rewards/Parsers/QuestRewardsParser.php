<?php

namespace App\Application\Accounts\Rewards\Parsers;

use App\Application\Accounts\Rewards\Data\CollectableQuestReward;
use JsonException;

/**
 * Reads Travian progressive task responses and extracts collectable rewards.
 */
class QuestRewardsParser
{
    /**
     * Determine whether a dorf1 document shows the progressive task alert bubble.
     */
    public function hasCollectableRewardIndicator(string $html): bool
    {
        return str_contains($html, 'newQuestSpeechBubble')
            || (str_contains($html, 'bigSpeechBubble') && str_contains($html, 'progressiveTasksTitle'));
    }

    /**
     * Extract collectable rewards from a tasks document, reload payload, or copied DevTools sample.
     *
     * @return list<CollectableQuestReward>
     */
    public function parseCollectableRewards(string $contents): array
    {
        $payload = $this->decodePayload($contents);

        if (! is_array($payload)) {
            return [];
        }

        $tasksData = $this->tasksDataFromPayload($payload);

        if (! is_array($tasksData)) {
            return [];
        }

        $heroLevel = $this->heroLevelFromPayload($payload, $tasksData);
        $rewards = [];

        foreach (['generalTasks', 'activeVillageTasks'] as $group) {
            $tasks = $tasksData[$group] ?? [];

            if (! is_array($tasks)) {
                continue;
            }

            foreach ($tasks as $task) {
                if (! is_array($task)) {
                    continue;
                }

                array_push($rewards, ...$this->collectableRewardsFromTask($task, $group, $heroLevel));
            }
        }

        return $rewards;
    }

    /**
     * @param  array<string, mixed>  $task
     * @return list<CollectableQuestReward>
     */
    protected function collectableRewardsFromTask(array $task, string $group, ?int $heroLevel): array
    {
        $questType = $task['type'] ?? null;
        $scope = $task['scope'] ?? null;
        $levels = $task['levels'] ?? [];

        if (! is_string($questType) || $questType === '' || ! is_string($scope) || $scope === '' || ! is_array($levels)) {
            return [];
        }

        $rewards = [];

        foreach ($levels as $level) {
            if (! is_array($level) || ($level['readyToBeCollected'] ?? false) !== true) {
                continue;
            }

            $targetLevel = $level['level'] ?? null;

            if (! is_numeric($targetLevel)) {
                continue;
            }

            $buildingId = $this->buildingIdFromMetadata($level['metadata'] ?? null)
                ?? $this->buildingIdFromMetadata($task['metadata'] ?? null);

            $rewards[] = new CollectableQuestReward(
                taskName: trim((string) ($task['name'] ?? $questType)),
                questType: $questType,
                scope: $scope,
                targetLevel: (int) $targetLevel,
                heroLevel: $heroLevel,
                buildingId: $buildingId,
                questId: (string) ($level['questId'] ?? $questType.'_'.$targetLevel),
                group: $group,
                levelTitle: trim((string) ($level['title'] ?? '')),
                rewardValues: $this->normalizeRewardValues($level['rewardValues'] ?? null),
            );
        }

        return $rewards;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodePayload(string $contents): ?array
    {
        $candidate = trim($this->extractCopiedResponse($contents));

        try {
            $decoded = json_decode($candidate, true, flags: JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (JsonException) {
        }

        return $this->extractReactTasksPayload($contents);
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
     * @return array<string, mixed>|null
     */
    protected function extractReactTasksPayload(string $html): ?array
    {
        $marker = 'window.Travian.React.Tasks.render(';
        $markerPosition = strpos($html, $marker);

        if ($markerPosition === false) {
            return null;
        }

        $offset = $markerPosition + strlen($marker);
        $json = $this->readFirstJavaScriptObject($html, $offset);

        if ($json === null) {
            return null;
        }

        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (JsonException) {
            return null;
        }
    }

    protected function readFirstJavaScriptObject(string $source, int $offset): ?string
    {
        $length = strlen($source);
        $start = strpos($source, '{', $offset);

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($index = $start; $index < $length; $index++) {
            $character = $source[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($character === '\\') {
                    $escaped = true;

                    continue;
                }

                if ($character === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($character === '"') {
                $inString = true;

                continue;
            }

            if ($character === '{') {
                $depth++;

                continue;
            }

            if ($character !== '}') {
                continue;
            }

            $depth--;

            if ($depth === 0) {
                return substr($source, $start, $index - $start + 1);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function tasksDataFromPayload(array $payload): ?array
    {
        foreach ([
            $payload['tasksData'] ?? null,
            $payload['tasks'] ?? null,
        ] as $tasksData) {
            if (is_array($tasksData)) {
                return $tasksData;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $tasksData
     */
    protected function heroLevelFromPayload(array $payload, array $tasksData): ?int
    {
        foreach ([
            $tasksData['rewardBonus']['heroLevel'] ?? null,
            $payload['rewardBonus']['heroLevel'] ?? null,
            $payload['heroData']['data']['level'] ?? null,
            $payload['hero']['data']['level'] ?? null,
        ] as $heroLevel) {
            if (is_numeric($heroLevel)) {
                return (int) $heroLevel;
            }
        }

        return null;
    }

    protected function buildingIdFromMetadata(mixed $metadata): ?int
    {
        if (! is_array($metadata) || ! is_numeric($metadata['buildingId'] ?? null)) {
            return null;
        }

        return (int) $metadata['buildingId'];
    }

    /**
     * @return array<string, int>
     */
    protected function normalizeRewardValues(mixed $rewardValues): array
    {
        if (! is_array($rewardValues)) {
            return [];
        }

        $normalized = [];

        foreach ($rewardValues as $key => $value) {
            if (is_string($key) && is_numeric($value)) {
                $normalized[$key] = (int) $value;
            }
        }

        return $normalized;
    }
}
