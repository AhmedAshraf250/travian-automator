<?php

namespace App\Application\Accounts\Sync\Parsers;

use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageResourceState;
use App\Application\Accounts\Sync\Data\ParsedVillageSummary;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

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

        return new ParsedDorf1Overview(
            activeVillage: $activeVillage,
            resourceState: $resourceState,
            villages: $villages,
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
        $population = $this->extractVillagePopulation($html);
        [$x, $y] = $this->extractVillageCoordinates($html, $travianVillageId);

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
     * Extract active village population from the embedded bootstrap data.
     */
    protected function extractVillagePopulation(string $html): ?int
    {
        if (! preg_match('/"population":(\d+)/', $html, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Extract active village coordinates from the embedded bootstrap data.
     *
     * @return array{0:?int,1:?int}
     */
    protected function extractVillageCoordinates(string $html, string $travianVillageId): array
    {
        $pattern = '/"village":\{"id":'.preg_quote($travianVillageId, '/').'.*?"x":(-?\d+),"y":(-?\d+)/s';

        if (! preg_match($pattern, $html, $matches)) {
            return [null, null];
        }

        return [(int) $matches[1], (int) $matches[2]];
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
