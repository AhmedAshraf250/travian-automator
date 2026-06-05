<?php

namespace App\Application\Accounts\Hero\Parsers;

use App\Application\Accounts\Hero\Data\ParsedHeroState;
use JsonException;

/**
 * Parses /api/v1/hero/dataForHUD responses into the local hero snapshot.
 */
class HeroHudDataParser
{
    /**
     * Parse the hero HUD JSON response.
     */
    public function parse(string $json): ?ParsedHeroState
    {
        try {
            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $statusTitle = html_entity_decode((string) ($payload['heroStatusTitle'] ?? $payload['heroButtonTitle'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return new ParsedHeroState(
            status: $this->resolveStatus($payload, $statusTitle),
            healthPercent: isset($payload['health']) ? (float) $payload['health'] : null,
            experiencePercent: isset($payload['experiencePercent']) ? (int) $payload['experiencePercent'] : null,
            level: isset($payload['level']) ? (int) $payload['level'] : null,
            adventuresAvailableCount: 0,
            hasUnspentAttributePoints: (bool) ($payload['levelUp'] ?? false),
            unspentAttributePoints: null,
            heroRemainingSeconds: $this->parseRemainingSeconds($statusTitle),
            homeVillageTravianId: $this->parseHomeVillageId((string) ($payload['url'] ?? '')),
            heroUri: '/hero',
            adventuresUri: '/hero/adventures',
            payload: [
                'source' => 'data_for_hud',
                'health_status' => $payload['healthStatus'] ?? null,
                'status_title' => $statusTitle,
            ],
        );
    }

    /**
     * Resolve Travian HUD state into a local status string.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function resolveStatus(array $payload, string $statusTitle): string
    {
        $inlineIcon = (string) ($payload['statusInlineIcon'] ?? '');
        $healthStatus = (string) ($payload['healthStatus'] ?? '');
        $normalizedTitle = mb_strtolower($statusTitle);

        if (str_contains($inlineIcon, 'heroReviving') || $healthStatus === 'regeneration') {
            return 'regenerating';
        }

        if (str_contains($inlineIcon, 'heroDead') || $healthStatus === 'dead') {
            return 'dead';
        }

        if (str_contains($inlineIcon, 'heroRunning')) {
            return str_contains($normalizedTitle, 'adventure') || str_contains($normalizedTitle, 'مغام')
                ? 'adventure'
                : 'returning';
        }

        return $healthStatus === 'alive' ? 'home' : 'unknown';
    }

    /**
     * Extract the movement countdown from the HUD tooltip.
     */
    protected function parseRemainingSeconds(string $statusTitle): ?int
    {
        if (preg_match('/value=["\']?(\d+)/u', $statusTitle, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Extract the Travian village id from the hero URL when available.
     */
    protected function parseHomeVillageId(string $url): ?string
    {
        if (preg_match('/(?:\?|&)newdid=([^&]+)/', $url, $matches) !== 1) {
            return null;
        }

        return urldecode($matches[1]);
    }
}
