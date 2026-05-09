<?php

namespace App\Application\Accounts\Sync\Parsers;

use App\Application\Accounts\Sync\Data\ParsedConstructionQueueEntry;
use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageMovementEntry;
use App\Application\Accounts\Sync\Data\ParsedVillageResourceState;
use App\Application\Accounts\Sync\Data\ParsedVillageRuntimeState;
use App\Application\Accounts\Sync\Data\ParsedVillageSummary;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use JsonException;

/**
 * Parses the Travian dorf1 HTML into structured village and resource data.
 */
class Dorf1OverviewParser
{
    /**
     * Parse a dorf1 overview page.
     */
    public function parse(string $html): ParsedDorf1Overview
    {
        $xpath = $this->createXPath($html);

        $villageNodes = $xpath->query("//div[@id='sidebarBoxVillageList']//div[contains(@class, 'listEntry') and contains(@class, 'village')]");

        if ($villageNodes === false || $villageNodes->length === 0) {
            throw new InvalidArgumentException('The provided HTML does not look like an authenticated dorf1 page.');
        }

        $activeVillage = $this->parseActiveVillage($xpath, $html);
        $villages = $this->parseVillageList($xpath);
        $resourceState = $this->parseResourceState($html);
        $runtimeState = $this->parseRuntimeState($xpath, $html, $activeVillage->travianVillageId);
        $constructionQueue = $this->parseConstructionQueue($xpath);

        return new ParsedDorf1Overview(
            activeVillage: $activeVillage,
            resourceState: $resourceState,
            runtimeState: $runtimeState,
            villages: $villages,
            constructionQueue: $constructionQueue,
        );
    }

    /**
     * Parse the active village summary.
     */
    protected function parseActiveVillage(DOMXPath $xpath, string $html): ParsedVillageSummary
    {
        $villageNameInput = $xpath->query("//div[@id='villageName']//input[@name='villageName']")?->item(0);

        if (! $villageNameInput instanceof DOMElement) {
            throw new InvalidArgumentException('Could not locate the active village name input.');
        }

        $travianVillageId = (string) $villageNameInput->getAttribute('data-did');
        $name = trim((string) $villageNameInput->getAttribute('value'));
        $activeVillageData = $this->extractActiveVillageData($html, $travianVillageId);
        $population = isset($activeVillageData['population'])
            ? (int) $activeVillageData['population']
            : $this->extractVillagePopulationFromMarkup($xpath);
        [$x, $y] = isset($activeVillageData['x'], $activeVillageData['y'])
            ? [(int) $activeVillageData['x'], (int) $activeVillageData['y']]
            : $this->extractVillageCoordinatesFromMarkup($xpath);

        return new ParsedVillageSummary(
            travianVillageId: $travianVillageId,
            name: $name,
            x: $x,
            y: $y,
            population: $population,
            isActive: true,
        );
    }

    /**
     * Parse the visible village list from the sidebar.
     *
     * @return list<ParsedVillageSummary>
     */
    protected function parseVillageList(DOMXPath $xpath): array
    {
        $villages = [];

        foreach ($xpath->query("//div[@id='sidebarBoxVillageList']//div[contains(@class, 'listEntry') and contains(@class, 'village')]") ?: [] as $villageElement) {
            if (! $villageElement instanceof DOMElement) {
                continue;
            }

            $nameNode = $xpath->query(".//span[contains(@class, 'name')]", $villageElement)?->item(0);

            $villages[] = new ParsedVillageSummary(
                travianVillageId: trim((string) $villageElement->getAttribute('data-did')),
                name: trim($nameNode?->textContent ?? ''),
                x: null,
                y: null,
                population: null,
                isActive: str_contains((string) $villageElement->getAttribute('class'), 'active'),
            );
        }

        return $villages;
    }

    /**
     * Parse current resources, production, and capacities.
     */
    protected function parseResourceState(string $html): ParsedVillageResourceState
    {
        $resources = $this->extractResourcesJson($html);

        return new ParsedVillageResourceState(
            wood: (int) ($resources['storage']['l1'] ?? 0),
            clay: (int) ($resources['storage']['l2'] ?? 0),
            iron: (int) ($resources['storage']['l3'] ?? 0),
            crop: (int) ($resources['storage']['l4'] ?? 0),
            woodProduction: (int) ($resources['production']['l1'] ?? 0),
            clayProduction: (int) ($resources['production']['l2'] ?? 0),
            ironProduction: (int) ($resources['production']['l3'] ?? 0),
            cropProduction: (int) ($resources['production']['l4'] ?? 0),
            freeCropProduction: (int) ($resources['production']['l5'] ?? 0),
            warehouseCapacity: (int) ($resources['maxStorage']['l1'] ?? 0),
            granaryCapacity: (int) ($resources['maxStorage']['l4'] ?? 0),
        );
    }

    /**
     * Parse runtime village data such as troops, movements, and hero status.
     */
    protected function parseRuntimeState(DOMXPath $xpath, string $html, string $travianVillageId): ParsedVillageRuntimeState
    {
        $viewData = $this->extractViewData($html);
        $tribeId = isset($viewData['ownPlayer']['tribeId']) ? (int) $viewData['ownPlayer']['tribeId'] : null;
        $movementEntries = $this->parseMovementEntries($xpath);
        $constructionEntries = $this->parseConstructionQueue($xpath);
        $incomingAttackCountFromMovements = array_sum(array_map(
            static fn (ParsedVillageMovementEntry $entry): int => $entry->kind === 'incoming_attack' ? $entry->count : 0,
            $movementEntries,
        ));
        $incomingReinforcementCount = array_sum(array_map(
            static fn (ParsedVillageMovementEntry $entry): int => $entry->kind === 'incoming_reinforcement' ? $entry->count : 0,
            $movementEntries,
        ));
        $outgoingMovementCount = array_sum(array_map(
            static fn (ParsedVillageMovementEntry $entry): int => $entry->kind === 'outgoing' ? $entry->count : 0,
            $movementEntries,
        ));
        [$heroStatus, $heroRemainingSeconds] = $this->parseHeroStatus($xpath);

        return new ParsedVillageRuntimeState(
            tribeId: $tribeId,
            troopSlots: $this->parseTroopSlots($xpath, $heroStatus),
            incomingAttackCount: max(
                $this->extractIncomingAttackCount($viewData, $travianVillageId),
                $incomingAttackCountFromMovements,
            ),
            incomingReinforcementCount: $incomingReinforcementCount,
            outgoingMovementCount: $outgoingMovementCount,
            movementEntries: $movementEntries,
            constructionEntries: $constructionEntries,
            heroStatus: $heroStatus,
            heroRemainingSeconds: $heroRemainingSeconds,
        );
    }

    /**
     * Parse the active construction queue from the dorf1 sidebar block.
     *
     * @return list<ParsedConstructionQueueEntry>
     */
    protected function parseConstructionQueue(DOMXPath $xpath): array
    {
        $entries = [];

        foreach ($xpath->query("//div[contains(@class, 'buildingList')]//ul/li") ?: [] as $index => $constructionNode) {
            if (! $constructionNode instanceof DOMElement) {
                continue;
            }

            $nameNode = $xpath->query(".//div[contains(@class, 'name')]", $constructionNode)?->item(0);
            $levelNode = $xpath->query(".//span[contains(@class, 'lvl')]", $constructionNode)?->item(0);
            $timerNode = $xpath->query(".//div[contains(@class, 'buildDuration')]//span[contains(@class, 'timer')]", $constructionNode)?->item(0);
            $durationNode = $xpath->query(".//div[contains(@class, 'buildDuration')]", $constructionNode)?->item(0);

            if (! $nameNode instanceof DOMElement || ! $timerNode instanceof DOMElement) {
                continue;
            }

            $buildingName = trim(str_replace($levelNode?->textContent ?? '', '', $nameNode->textContent));
            $targetLevel = $levelNode instanceof DOMElement ? ($this->extractInteger($levelNode->textContent) ?? 0) : 0;
            $remainingSeconds = (int) ($timerNode->getAttribute('data-value') ?: $timerNode->getAttribute('value') ?: 0);
            $remainingLabel = trim($timerNode->textContent);
            $finishLabel = $durationNode instanceof DOMElement ? $this->extractFinishLabel($durationNode->textContent) : null;

            $entries[] = new ParsedConstructionQueueEntry(
                buildingName: $buildingName !== '' ? $buildingName : 'Unknown building',
                targetLevel: $targetLevel,
                remainingSeconds: $remainingSeconds,
                remainingLabel: $remainingLabel !== '' ? $remainingLabel : null,
                finishLabel: $finishLabel,
            );
        }

        return $entries;
    }

    /**
     * Parse the troops currently visible in the village infobox.
     *
     * @return list<int>
     */
    protected function parseTroopSlots(DOMXPath $xpath, ?string $heroStatus): array
    {
        $slots = array_fill(0, 10, 0);

        foreach ($xpath->query("//table[@id='troops']//tbody/tr") ?: [] as $troopNode) {
            if (! $troopNode instanceof DOMElement) {
                continue;
            }

            $iconNode = $xpath->query(".//img[contains(@class, 'unit')]", $troopNode)?->item(0);
            $countNode = $xpath->query(".//td[contains(@class, 'num')]", $troopNode)?->item(0);
            $unitNameNode = $xpath->query(".//td[contains(@class, 'un')]", $troopNode)?->item(0);

            if (! $countNode instanceof DOMElement) {
                continue;
            }

            $rowText = trim($troopNode->textContent);
            $count = $this->extractInteger($countNode->textContent) ?? 0;
            $iconClass = $iconNode instanceof DOMElement ? (string) $iconNode->getAttribute('class') : '';
            $iconTitle = $iconNode instanceof DOMElement ? trim((string) $iconNode->getAttribute('title')) : '';
            $iconAlt = $iconNode instanceof DOMElement ? trim((string) $iconNode->getAttribute('alt')) : '';
            $unitName = $unitNameNode instanceof DOMElement ? trim($unitNameNode->textContent) : '';
            $heroHaystack = mb_strtolower("{$iconClass} {$iconTitle} {$iconAlt} {$unitName} {$rowText}");

            if (str_contains($heroHaystack, 'uhero') || str_contains($heroHaystack, 'hero') || str_contains($heroHaystack, 'بطل')) {
                $slots[0] = $count > 0 ? $count : 1;

                continue;
            }

            if (! $iconNode instanceof DOMElement || ! preg_match('/\bu(\d+)\b/', $iconClass, $matches)) {
                continue;
            }

            $unitIndex = (int) $matches[1];

            if ($unitIndex >= 10) {
                continue;
            }

            $slotIndex = $unitIndex;

            if ($slotIndex >= count($slots)) {
                $slots = array_pad($slots, $slotIndex + 1, 0);
            }

            $slots[$slotIndex] = $count;
        }

        return array_values($slots);
    }

    /**
     * Parse the visible movement infobox rows.
     *
     * @return list<ParsedVillageMovementEntry>
     */
    protected function parseMovementEntries(DOMXPath $xpath): array
    {
        $entries = [];
        $currentSection = null;

        foreach ($xpath->query("//table[@id='movements']//tr") ?: [] as $movementNode) {
            if (! $movementNode instanceof DOMElement) {
                continue;
            }

            $headerNode = $xpath->query(".//th[contains(@class, 'troopMovements')]", $movementNode)?->item(0);

            if ($headerNode instanceof DOMElement) {
                $currentSection = $this->inferMovementSection(trim($headerNode->textContent));

                continue;
            }

            $iconNode = $xpath->query(".//td[contains(@class, 'typ')]//img", $movementNode)?->item(0);
            $labelNode = $xpath->query(".//div[contains(@class, 'mov')]//span[1]", $movementNode)?->item(0);
            $timerNode = $xpath->query(".//span[contains(@class, 'timer')]", $movementNode)?->item(0);

            if (! $labelNode instanceof DOMElement) {
                continue;
            }

            $label = trim($labelNode->textContent);
            $iconClass = $iconNode instanceof DOMElement ? (string) $iconNode->getAttribute('class') : '';
            $iconTitle = $iconNode instanceof DOMElement ? (string) $iconNode->getAttribute('title') : '';

            $entries[] = new ParsedVillageMovementEntry(
                kind: $this->inferMovementKind($iconClass, $iconTitle, $label, $currentSection),
                label: $label,
                count: max(1, $this->extractInteger($label) ?? 1),
                remainingSeconds: $timerNode instanceof DOMElement
                    ? (int) ($timerNode->getAttribute('data-value') ?: $timerNode->getAttribute('value') ?: 0)
                    : null,
                remainingLabel: $timerNode instanceof DOMElement
                    ? (trim($timerNode->textContent) !== '' ? trim($timerNode->textContent) : null)
                    : null,
            );
        }

        return $entries;
    }

    /**
     * Extract the JS resources object from the HTML.
     *
     * @return array<string, array<string, int>>
     */
    protected function extractResourcesJson(string $html): array
    {
        if (! preg_match('/var resources = \{\s*production: (\{.*?\}),\s*storage: (\{.*?\}),\s*maxStorage: (\{.*?\})\s*\};/s', $html, $matches)) {
            throw new InvalidArgumentException('Could not parse the resources object from dorf1 HTML.');
        }

        return [
            'production' => json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR),
            'storage' => json_decode($matches[2], true, flags: JSON_THROW_ON_ERROR),
            'maxStorage' => json_decode($matches[3], true, flags: JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * Extract the active village data from the embedded bootstrap payload.
     *
     * @return array{population?:int,x?:int,y?:int}
     */
    protected function extractActiveVillageData(string $html, string $travianVillageId): array
    {
        $viewData = $this->extractViewData($html);
        $village = $viewData['ownPlayer']['village'] ?? null;

        if (! is_array($village)) {
            return [];
        }

        if ((string) ($village['id'] ?? '') !== $travianVillageId) {
            return [];
        }

        return $village;
    }

    /**
     * Extract the modern bootstrap view data object from the page.
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

            if ($depth !== 0) {
                continue;
            }

            $json = substr($html, $jsonStart, $index - $jsonStart + 1);

            try {
                return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return [];
            }
        }

        return [];
    }

    /**
     * Extract incoming attack count for the active village from the bootstrap data.
     *
     * @param  array<string, mixed>  $viewData
     */
    protected function extractIncomingAttackCount(array $viewData, string $travianVillageId): int
    {
        $villageEntries = $viewData['ownPlayer']['villageList'] ?? [];

        if (! is_array($villageEntries)) {
            return 0;
        }

        return $this->findIncomingAttackCount($villageEntries, $travianVillageId);
    }

    /**
     * Walk the mixed village list payload until the active village is found.
     *
     * @param  list<mixed>  $entries
     */
    protected function findIncomingAttackCount(array $entries, string $travianVillageId): int
    {
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if ((string) ($entry['id'] ?? '') === $travianVillageId) {
                return (int) ($entry['incomingAttacksAmount'] ?? 0);
            }

            if (isset($entry['villages']) && is_array($entry['villages'])) {
                $count = $this->findIncomingAttackCount($entry['villages'], $travianVillageId);

                if ($count > 0) {
                    return $count;
                }
            }
        }

        return 0;
    }

    /**
     * Extract active village population from visible markup as a fallback.
     */
    protected function extractVillagePopulationFromMarkup(DOMXPath $xpath): ?int
    {
        $populationNode = $xpath->query("//div[contains(@class, 'population')]//span")?->item(0);

        if (! $populationNode instanceof DOMElement) {
            return null;
        }

        return $this->extractInteger($populationNode->textContent);
    }

    /**
     * Extract active village coordinates from visible markup as a fallback.
     *
     * @return array{0:?int,1:?int}
     */
    protected function extractVillageCoordinatesFromMarkup(DOMXPath $xpath): array
    {
        $xNode = $xpath->query("//span[contains(@class, 'coordinatesGrid')]//span[contains(@class, 'coordinateX')]")?->item(0);
        $yNode = $xpath->query("//span[contains(@class, 'coordinatesGrid')]//span[contains(@class, 'coordinateY')]")?->item(0);

        return [
            $xNode instanceof DOMElement ? $this->extractInteger($xNode->textContent) : null,
            $yNode instanceof DOMElement ? $this->extractInteger($yNode->textContent) : null,
        ];
    }

    /**
     * Extract a signed integer from mixed UI text.
     */
    protected function extractInteger(string $value): ?int
    {
        if (! preg_match('/-?\d+/', preg_replace('/[^\d-]+/u', ' ', $value) ?? '', $matches)) {
            return null;
        }

        return (int) $matches[0];
    }

    /**
     * Extract the visible finish time label from queue text.
     */
    protected function extractFinishLabel(string $value): ?string
    {
        if (! preg_match_all('/(\d{1,2}:\d{2})/u', $value, $matches) || empty($matches[1])) {
            return null;
        }

        return end($matches[1]) ?: null;
    }

    /**
     * Parse the hero status block to know whether the hero is away or home.
     *
     * @return array{0:?string,1:?int}
     */
    protected function parseHeroStatus(DOMXPath $xpath): array
    {
        $heroContainer = $xpath->query("//*[@id='topBarHero']")?->item(0);

        if (! $heroContainer instanceof DOMElement) {
            return [null, null];
        }

        $heroRunningNode = $xpath->query(".//*[contains(@class, 'heroRunning')]", $heroContainer)?->item(0);
        $timerNode = $xpath->query(".//span[contains(@class, 'timer')]", $heroContainer)?->item(0);
        $heroTitleNode = $xpath->query(".//*[@id='heroImageButton' and @title] | .//*[contains(@class, 'heroStatus') and @title]", $heroContainer)?->item(0);
        $heroTitle = trim((string) ($heroTitleNode instanceof DOMElement ? $heroTitleNode->getAttribute('title') : ''));
        $heroStatus = 'home';

        if ($heroRunningNode instanceof DOMElement) {
            $normalizedHeroTitle = mb_strtolower(html_entity_decode($heroTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $heroStatus = match (true) {
                str_contains($normalizedHeroTitle, 'adventure'),
                str_contains($normalizedHeroTitle, 'مغام') => 'adventure',
                str_contains($normalizedHeroTitle, 'way back'),
                str_contains($normalizedHeroTitle, 'return'),
                str_contains($normalizedHeroTitle, 'عودة') => 'returning',
                default => 'returning',
            };
        }

        $heroRemainingSeconds = null;

        if ($timerNode instanceof DOMElement) {
            $heroRemainingSeconds = (int) ($timerNode->getAttribute('data-value') ?: $timerNode->getAttribute('value') ?: 0);
        } elseif (preg_match('/value=&quot;(\d+)&quot;/u', $heroTitle, $matches)) {
            $heroRemainingSeconds = (int) $matches[1];
        } elseif (preg_match('/\b(\d{1,2}):(\d{2}):(\d{2})\b/u', html_entity_decode($heroTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches)) {
            $heroRemainingSeconds = ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
        }

        return [
            $heroStatus,
            $heroRemainingSeconds,
        ];
    }

    /**
     * Infer the movement kind from the icon and localized label.
     */
    protected function inferMovementKind(string $iconClass, string $iconTitle, string $label, ?string $section): string
    {
        $haystack = mb_strtolower("{$iconClass} {$iconTitle} {$label}");

        if (str_contains($haystack, 'def') || str_contains($haystack, 'تعزيز') || str_contains($haystack, 'المتعاونة')) {
            return 'incoming_reinforcement';
        }

        if ($section === 'outgoing') {
            return 'outgoing';
        }

        if ($section === 'incoming' && (str_contains($haystack, 'attack') || str_contains($haystack, 'هجوم'))) {
            return 'incoming_attack';
        }

        if (str_contains($haystack, 'att') || str_contains($haystack, 'هجوم')) {
            return 'incoming_attack';
        }

        if (
            str_contains($haystack, 'out')
            || str_contains($haystack, 'hero_on_adventure')
            || str_contains($haystack, 'adventure')
            || str_contains($haystack, 'عودة')
            || str_contains($haystack, 'صادرة')
            || str_contains($haystack, 'مغام')
        ) {
            return 'outgoing';
        }

        return 'other';
    }

    /**
     * Infer the current movement table section from its header text.
     */
    protected function inferMovementSection(string $header): ?string
    {
        $normalizedHeader = mb_strtolower($header);

        if (str_contains($normalizedHeader, 'incoming') || str_contains($normalizedHeader, 'قادمة')) {
            return 'incoming';
        }

        if (str_contains($normalizedHeader, 'outgoing') || str_contains($normalizedHeader, 'مرسلة')) {
            return 'outgoing';
        }

        return null;
    }

    /**
     * Create an XPath instance for the provided HTML string.
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
