<?php

namespace App\Application\Accounts\Hero\Parsers;

use App\Application\Accounts\Hero\Data\HeroAttributesAnalysis;
use App\Application\Accounts\Hero\Data\ParsedHeroState;
use DOMDocument;
use DOMElement;
use DOMXPath;
use JsonException;

/**
 * Parses hero attributes API responses and builds attribute update payloads.
 */
class HeroAttributesAnalyzer
{
    /**
     * Analyze the /api/v1/hero/v2/screen/attributes JSON response.
     */
    public function analyze(string $json): ?HeroAttributesAnalysis
    {
        try {
            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload) || ! is_array($payload['hero'] ?? null)) {
            return null;
        }

        $hero = $payload['hero'];
        $revive = is_array($payload['revive'] ?? null)
            ? $payload['revive']
            : (is_array($hero['revive'] ?? null) ? $hero['revive'] : null);
        $reviveBlockedMessage = is_string($revive['chargesErrorMessage'] ?? null)
            ? $revive['chargesErrorMessage']
            : null;

        return new HeroAttributesAnalysis(
            heroState: $this->parseHeroState(
                $hero,
                is_array($payload['heroState'] ?? null) ? $payload['heroState'] : null,
            ),
            freePoints: max(0, (int) ($hero['freePoints'] ?? 0)),
            attributeUsedPoints: $this->parseAttributeUsedPoints($hero),
            canReviveWithResources: $revive !== null
                && $reviveBlockedMessage === null,
            reviveRequiredResources: $revive !== null ? $this->parseResourceCharges((string) ($revive['charges'] ?? '')) : [],
            reviveDurationSeconds: $revive !== null ? $this->parseDurationSeconds((string) ($revive['chargesDuration'] ?? '')) : null,
            reviveDurationLabel: $revive !== null ? $this->parseDurationLabel((string) ($revive['chargesDuration'] ?? '')) : null,
            reviveBlockedMessage: $reviveBlockedMessage,
            payload: $payload,
        );
    }

    /**
     * Build a Travian-compatible attribute increment payload from fixed weights.
     *
     * @param  array<string, mixed>  $weights
     * @return array{power: int, offBonus: int, defBonus: int, productionPoints: int}|array{}
     */
    public function buildDistributionPayload(array $weights, int $freePoints): array
    {
        $normalizedWeights = [
            'power' => max(0, (int) ($weights['power'] ?? 0)),
            'offBonus' => max(0, (int) ($weights['offBonus'] ?? 0)),
            'defBonus' => max(0, (int) ($weights['defBonus'] ?? 0)),
            'productionPoints' => max(0, (int) ($weights['productionPoints'] ?? 0)),
        ];
        $totalWeight = array_sum($normalizedWeights);

        if ($freePoints <= 0 || $totalWeight <= 0) {
            return [];
        }

        $payload = [
            'power' => 0,
            'offBonus' => 0,
            'defBonus' => 0,
            'productionPoints' => 0,
        ];
        $assigned = 0;

        foreach ($normalizedWeights as $attribute => $weight) {
            if ($weight <= 0) {
                continue;
            }

            $points = intdiv($freePoints * $weight, $totalWeight);

            if ($points > 0) {
                $payload[$attribute] = $points;
                $assigned += $points;
            }
        }

        $remaining = $freePoints - $assigned;

        foreach ($normalizedWeights as $attribute => $weight) {
            if ($remaining <= 0) {
                break;
            }

            if ($weight <= 0) {
                continue;
            }

            $payload[$attribute] = ($payload[$attribute] ?? 0) + 1;
            $remaining--;
        }

        return $payload;
    }

    /**
     * Parse hero state fields from the attributes API.
     *
     * @param  array<string, mixed>  $hero
     * @param  array<string, mixed>|null  $apiHeroState
     */
    protected function parseHeroState(array $hero, ?array $apiHeroState): ParsedHeroState
    {
        $heroState = $apiHeroState ?? (is_array($hero['heroState'] ?? null) ? $hero['heroState'] : []);
        $status = is_array($heroState['status'] ?? null) ? $heroState['status'] : [];

        return new ParsedHeroState(
            status: $this->resolveStatus($heroState, $status),
            healthPercent: isset($hero['health']) ? (float) $hero['health'] : null,
            experiencePercent: isset($hero['experiencePercent']) ? (int) $hero['experiencePercent'] : null,
            level: null,
            adventuresAvailableCount: 0,
            hasUnspentAttributePoints: ((int) ($hero['freePoints'] ?? 0)) > 0,
            unspentAttributePoints: isset($hero['freePoints']) ? (int) $hero['freePoints'] : null,
            heroRemainingSeconds: isset($status['arrivalIn']) ? (int) $status['arrivalIn'] : ($heroState['regenerationTimeLeft'] ?? null),
            homeVillageTravianId: isset($heroState['homeVillage']['id']) ? (string) $heroState['homeVillage']['id'] : null,
            heroUri: '/hero',
            adventuresUri: '/hero/adventures',
            payload: [
                'source' => 'attributes_api',
            ],
        );
    }

    /**
     * Resolve a local hero status from attributes API state.
     *
     * @param  array<string, mixed>  $heroState
     * @param  array<string, mixed>  $status
     */
    protected function resolveStatus(array $heroState, array $status): string
    {
        if ((bool) ($heroState['isRegenerating'] ?? false)) {
            return 'regenerating';
        }

        if (($status['adventure'] ?? null) !== null) {
            return 'adventure';
        }

        if (($status['arrivalIn'] ?? null) !== null || ($status['onWayTo'] ?? null) !== null) {
            return 'returning';
        }

        if (($status['inVillage'] ?? null) !== null) {
            return 'home';
        }

        return ((int) ($status['status'] ?? 0)) > 100 ? 'dead' : 'unknown';
    }

    /**
     * Extract the current used points from each supported attribute.
     *
     * @param  array<string, mixed>  $hero
     * @return array<string, int>
     */
    protected function parseAttributeUsedPoints(array $hero): array
    {
        $attributes = is_array($hero['attributes'] ?? null) ? $hero['attributes'] : [];
        $usedPoints = [];

        foreach (['power', 'offBonus', 'defBonus', 'productionPoints'] as $attribute) {
            $usedPoints[$attribute] = isset($attributes[$attribute]['usedPoints'])
                ? (int) $attributes[$attribute]['usedPoints']
                : 0;
        }

        return $usedPoints;
    }

    /**
     * Parse revive resource charges from embedded HTML.
     *
     * @return array{wood?: int, clay?: int, iron?: int, crop?: int, crop_consumption?: int}
     */
    protected function parseResourceCharges(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $xpath = $this->createXPath($html);
        $resources = [];
        $map = [
            'r1Big' => 'wood',
            'r2Big' => 'clay',
            'r3Big' => 'iron',
            'r4Big' => 'crop',
            'cropConsumptionBig' => 'crop_consumption',
        ];

        foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' inlineIcon ')]") ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $icon = $xpath->query('.//i', $node)?->item(0);
            $value = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' value ')]", $node)?->item(0);

            if (! $icon instanceof DOMElement || ! $value instanceof DOMElement) {
                continue;
            }

            foreach ($map as $class => $key) {
                if (! str_contains($icon->getAttribute('class'), $class)) {
                    continue;
                }

                $resources[$key] = $this->parseInteger($value->textContent);

                break;
            }
        }

        return $resources;
    }

    /**
     * Parse the revive duration as seconds.
     */
    protected function parseDurationSeconds(string $html): ?int
    {
        $label = $this->parseDurationLabel($html);

        if ($label === null) {
            return null;
        }

        if (preg_match('/(\d{1,2}):(\d{2}):(\d{2})/u', $label, $matches) !== 1) {
            return null;
        }

        return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
    }

    /**
     * Parse the visible revive duration label.
     */
    protected function parseDurationLabel(string $html): ?string
    {
        if (trim($html) === '') {
            return null;
        }

        $xpath = $this->createXPath($html);
        $node = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' value ')]")?->item(0);

        if (! $node instanceof DOMElement) {
            return null;
        }

        $label = trim($node->textContent);

        return $label !== '' ? $label : null;
    }

    /**
     * Extract an integer from localized UI text.
     */
    protected function parseInteger(string $value): int
    {
        return (int) (preg_replace('/[^\d]/u', '', $value) ?? '');
    }

    /**
     * Create an XPath parser for embedded HTML fragments.
     */
    protected function createXPath(string $html): DOMXPath
    {
        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }
}
