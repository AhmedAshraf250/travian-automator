<?php

namespace App\Application\Accounts\Troops\Parsers;

use App\Application\Accounts\Troops\Data\ParsedResearchPage;
use App\Application\Accounts\Troops\Data\ParsedResearchUnit;
use App\Application\Accounts\Troops\Data\ParsedTroopQueueEntry;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

abstract class ResearchBuildingPageParser
{
    protected function parseResearchPage(string $html, bool $includeCurrentLevel): ParsedResearchPage
    {
        $xpath = $this->createXPath($html);
        $units = [];

        foreach ($xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' researches ')]/div[contains(concat(' ', normalize-space(@class), ' '), ' research ')]") ?: [] as $researchNode) {
            if (! $researchNode instanceof DOMElement) {
                continue;
            }

            $unitId = $this->extractUnitId($xpath, $researchNode);

            if ($unitId === null) {
                continue;
            }

            $levelNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' title ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' level ')]", $researchNode)?->item(0);
            $durationNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' duration ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' value ')]", $researchNode)?->item(0);
            $messageNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' errorMessage ')]", $researchNode)?->item(0);

            $units[$unitId] = new ParsedResearchUnit(
                unitId: $unitId,
                currentLevel: $includeCurrentLevel && $levelNode instanceof DOMNode
                    ? ($this->extractFirstInteger($levelNode->textContent) ?? 0)
                    : null,
                cost: $this->extractResources($xpath, $researchNode),
                durationSeconds: $durationNode instanceof DOMNode ? $this->durationToSeconds($durationNode->textContent) : 0,
                actionUri: $this->extractResearchActionUri($researchNode),
                requirements: $this->extractRequirements($xpath, $researchNode),
                serverMessage: $messageNode instanceof DOMNode ? trim($messageNode->textContent) : null,
                hasResourceShortage: $this->hasResourceShortage($xpath, $researchNode),
            );
        }

        return new ParsedResearchPage(
            units: array_values($units),
            queue: $this->parseQueue($xpath, $includeCurrentLevel),
        );
    }

    protected function hasResourceShortage(DOMXPath $xpath, DOMElement $node): bool
    {
        return $xpath->query(
            ".//*[contains(concat(' ', normalize-space(@class), ' '), ' resource ') and contains(concat(' ', normalize-space(@class), ' '), ' transfer ') and contains(concat(' ', normalize-space(@class), ' '), ' fillUp ')]",
            $node,
        )?->length > 0;
    }

    /** @return list<ParsedTroopQueueEntry> */
    protected function parseQueue(DOMXPath $xpath, bool $includeTargetLevel): array
    {
        $entries = [];

        foreach ($xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' under_progress ')]//tbody/tr") ?: [] as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            $unitId = $this->extractUnitId($xpath, $row);
            $timer = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' timer ')]", $row)?->item(0);
            $level = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' level ')]", $row)?->item(0);

            if ($unitId === null) {
                continue;
            }

            $entries[] = new ParsedTroopQueueEntry(
                unitId: $unitId,
                quantity: 0,
                remainingSeconds: $timer instanceof DOMElement
                    ? ((int) $timer->getAttribute('value') ?: $this->durationToSeconds($timer->textContent))
                    : 0,
                targetLevel: $includeTargetLevel && $level instanceof DOMNode
                    ? $this->extractLastInteger($level->textContent)
                    : null,
            );
        }

        return $entries;
    }

    protected function extractResearchActionUri(DOMElement $node): ?string
    {
        $html = $node->ownerDocument?->saveHTML($node) ?: '';

        if (preg_match('/(\/build\.php[^"\']*action=research[^"\']*)/u', $html, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** @return list<array{gid: int, required_level: int, current_level: int|null, met: bool}> */
    protected function extractRequirements(DOMXPath $xpath, DOMElement $node): array
    {
        $requirements = [];

        foreach ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' requirements ')]/span", $node) ?: [] as $requirementNode) {
            if (! $requirementNode instanceof DOMElement) {
                continue;
            }

            $html = $requirementNode->ownerDocument?->saveHTML($requirementNode) ?: '';

            if (preg_match("/Manual\.open\('building',\s*(\d+)\)/u", $html, $matches) !== 1) {
                continue;
            }

            $levelNode = $xpath->query('.//span', $requirementNode)?->item(0);
            $requiredLevel = $levelNode instanceof DOMNode ? ($this->extractLastInteger($levelNode->textContent) ?? 0) : 0;
            $currentLevel = $levelNode instanceof DOMElement ? $this->extractFirstInteger($levelNode->getAttribute('title')) : null;
            $isMissing = str_contains(' '.$requirementNode->getAttribute('class').' ', ' error ');

            $requirements[] = [
                'gid' => (int) $matches[1],
                'required_level' => $requiredLevel,
                'current_level' => $currentLevel,
                'met' => ! $isMissing,
            ];
        }

        return $requirements;
    }

    /** @return array{wood: int, clay: int, iron: int, crop: int} */
    protected function extractResources(DOMXPath $xpath, DOMElement $node): array
    {
        $resources = ['wood' => 0, 'clay' => 0, 'iron' => 0, 'crop' => 0];
        $classMap = [
            'r1Big' => 'wood', 'lumber' => 'wood',
            'r2Big' => 'clay', 'clay' => 'clay',
            'r3Big' => 'iron', 'iron' => 'iron',
            'r4Big' => 'crop', 'crop' => 'crop',
        ];

        foreach ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' resourceWrapper ') and contains(concat(' ', normalize-space(@class), ' '), ' charges ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' resource ')]", $node) ?: [] as $resourceNode) {
            if (! $resourceNode instanceof DOMElement) {
                continue;
            }

            $icon = $xpath->query('.//i', $resourceNode)?->item(0);
            $value = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' value ')]", $resourceNode)?->item(0);

            if (! $icon instanceof DOMElement || ! $value instanceof DOMNode) {
                continue;
            }

            foreach ($classMap as $class => $key) {
                if (str_contains(' '.$icon->getAttribute('class').' ', ' '.$class.' ')) {
                    $resources[$key] = $this->extractDigits($value->textContent) ?? 0;
                }
            }
        }

        return $resources;
    }

    protected function extractUnitId(DOMXPath $xpath, DOMElement $node): ?int
    {
        foreach ($xpath->query(".//img[contains(concat(' ', normalize-space(@class), ' '), ' unit ')]", $node) ?: [] as $image) {
            if ($image instanceof DOMElement && preg_match('/(?:^|\s)u(\d+)(?:\s|$)/u', $image->getAttribute('class'), $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    protected function extractFirstInteger(string $value): ?int
    {
        preg_match('/\d+/u', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches);

        return isset($matches[0]) ? (int) $matches[0] : null;
    }

    protected function extractLastInteger(string $value): ?int
    {
        preg_match_all('/\d+/u', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches);
        $values = $matches[0] ?? [];

        return $values === [] ? null : (int) end($values);
    }

    protected function extractDigits(string $value): ?int
    {
        $digits = preg_replace('/[^\d]+/u', '', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';

        return $digits === '' ? null : (int) $digits;
    }

    protected function durationToSeconds(string $duration): int
    {
        $parts = array_map('intval', explode(':', trim($duration)));

        return match (count($parts)) {
            3 => ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2],
            2 => ($parts[0] * 60) + $parts[1],
            default => $parts[0] ?? 0,
        };
    }

    protected function createXPath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }
}
