<?php

namespace App\Application\Accounts\Hero\Parsers;

use App\Application\Accounts\Hero\Data\HeroAdventurePageAnalysis;
use App\Application\Accounts\Hero\Data\ParsedHeroAdventure;
use App\Application\Accounts\Hero\Data\ParsedHeroState;
use DOMDocument;
use DOMElement;
use DOMXPath;
use JsonException;

/**
 * Parses the hero adventures document into available adventure actions.
 */
class HeroAdventurePageAnalyzer
{
    /**
     * Analyze the /hero/adventures document.
     */
    public function analyze(string $html): HeroAdventurePageAnalysis
    {
        $viewData = $this->extractViewData($html);
        $adventures = $this->extractAdventuresFromViewData($viewData);

        if ($adventures === []) {
            $adventures = $this->extractAdventuresFromTable($html);
        }

        return new HeroAdventurePageAnalysis(
            adventures: $adventures,
            heroState: $this->extractHeroStateFromViewData($viewData),
        );
    }

    /**
     * Extract the React viewData JSON embedded in the page.
     *
     * @return array<string, mixed>
     */
    protected function extractViewData(string $html): array
    {
        $labelPosition = strpos($html, 'viewData:');

        if ($labelPosition === false) {
            return [];
        }

        $jsonStart = strpos($html, '{', $labelPosition);

        if ($jsonStart === false) {
            return [];
        }

        $json = $this->extractBalancedObject($html, $jsonStart);

        if ($json === null) {
            return [];
        }

        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Extract available adventures from React viewData.
     *
     * @param  array<string, mixed>  $viewData
     * @return list<ParsedHeroAdventure>
     */
    protected function extractAdventuresFromViewData(array $viewData): array
    {
        $adventures = $viewData['data']['ownPlayer']['hero']['adventures'] ?? [];

        if (! is_array($adventures)) {
            return [];
        }

        $parsed = [];

        foreach ($adventures as $adventure) {
            if (! is_array($adventure) || ! isset($adventure['number'])) {
                continue;
            }

            $parsed[] = new ParsedHeroAdventure(
                number: (int) $adventure['number'],
                place: isset($adventure['place']) ? (string) $adventure['place'] : null,
                difficulty: isset($adventure['difficulty']) ? (int) $adventure['difficulty'] : null,
                travelingDuration: isset($adventure['travelingDuration']) ? (int) $adventure['travelingDuration'] : null,
                payload: $adventure,
            );
        }

        return $parsed;
    }

    /**
     * Extract a hero state snapshot from adventure viewData.
     *
     * @param  array<string, mixed>  $viewData
     */
    protected function extractHeroStateFromViewData(array $viewData): ?ParsedHeroState
    {
        $hero = $viewData['data']['ownPlayer']['hero'] ?? null;

        if (! is_array($hero)) {
            return null;
        }

        $status = is_array($hero['status'] ?? null) ? $hero['status'] : [];
        $statusName = $this->resolveHeroStatus($hero, $status);

        return new ParsedHeroState(
            status: $statusName,
            healthPercent: null,
            experiencePercent: null,
            level: null,
            adventuresAvailableCount: is_array($hero['adventures'] ?? null) ? count($hero['adventures']) : 0,
            hasUnspentAttributePoints: false,
            unspentAttributePoints: null,
            heroRemainingSeconds: isset($status['arrivalIn']) ? (int) $status['arrivalIn'] : null,
            homeVillageTravianId: isset($hero['homeVillage']['id']) ? (string) $hero['homeVillage']['id'] : null,
            heroUri: '/hero',
            adventuresUri: '/hero/adventures',
            payload: [
                'source' => 'adventure_view_data',
            ],
        );
    }

    /**
     * Infer the hero movement state from viewData.
     *
     * @param  array<string, mixed>  $hero
     * @param  array<string, mixed>  $status
     */
    protected function resolveHeroStatus(array $hero, array $status): string
    {
        if ((bool) ($hero['isRegenerating'] ?? false)) {
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
     * Fallback table parser. It cannot recover adventure ids, so it is read-only.
     *
     * @return list<ParsedHeroAdventure>
     */
    protected function extractAdventuresFromTable(string $html): array
    {
        $xpath = $this->createXPath($html);
        $adventures = [];
        $rowIndex = 0;

        foreach ($xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' adventureList ')]//tbody/tr") ?: [] as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            $rowIndex++;
            $placeNode = $xpath->query(".//td[contains(concat(' ', normalize-space(@class), ' '), ' place ')]//img", $row)?->item(0);
            $difficultyNode = $xpath->query(".//*[contains(@class, 'difficulty_')]", $row)?->item(0);
            $durationNode = $xpath->query(".//td[contains(concat(' ', normalize-space(@class), ' '), ' duration ')]//div[contains(concat(' ', normalize-space(@class), ' '), ' duration ')]", $row)?->item(0);
            $place = $placeNode instanceof DOMElement ? (string) ($placeNode->getAttribute('class') ?: $placeNode->getAttribute('alt')) : null;
            $difficulty = $difficultyNode instanceof DOMElement && str_contains($difficultyNode->getAttribute('class'), 'difficulty_hard') ? 3 : null;

            $adventures[] = new ParsedHeroAdventure(
                number: 0 - $rowIndex,
                place: $place,
                difficulty: $difficulty,
                travelingDuration: $durationNode instanceof DOMElement ? $this->durationToSeconds($durationNode->textContent) : null,
                payload: ['source' => 'table_fallback'],
            );
        }

        return $adventures;
    }

    /**
     * Extract a balanced JSON object from a larger JS snippet.
     */
    protected function extractBalancedObject(string $html, int $jsonStart): ?string
    {
        $depth = 0;
        $inString = false;
        $isEscaped = false;
        $length = strlen($html);

        for ($index = $jsonStart; $index < $length; $index++) {
            $character = $html[$index];

            if ($inString) {
                if ($isEscaped) {
                    $isEscaped = false;

                    continue;
                }

                if ($character === '\\') {
                    $isEscaped = true;

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
                return substr($html, $jsonStart, $index - $jsonStart + 1);
            }
        }

        return null;
    }

    /**
     * Convert a H:i:s or i:s label into seconds.
     */
    protected function durationToSeconds(string $label): ?int
    {
        if (preg_match('/(\d{1,2}):(\d{2}):(\d{2})/u', $label, $matches) === 1) {
            return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
        }

        if (preg_match('/(\d{1,2}):(\d{2})/u', $label, $matches) !== 1) {
            return null;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    /**
     * Create an XPath parser for the fallback table.
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
