<?php

namespace App\Application\Accounts\Sync\Parsers;

use App\Application\Accounts\Sync\Data\ParsedDorf2Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageSlot;
use App\Application\Travian\TravianBuildingCatalog;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

/**
 * Parses the Travian dorf2 village-center page into structured building-slot data.
 */
class Dorf2OverviewParser
{
    /**
     * Parse a dorf2 page into building slots.
     */
    public function parse(string $html): ParsedDorf2Overview
    {
        $xpath = $this->createXPath($html);
        $slotNodes = $xpath->query("//div[contains(@class, 'buildingSlot')][@data-aid]");

        if ($slotNodes === false || $slotNodes->length === 0) {
            throw new InvalidArgumentException('The provided HTML does not look like an authenticated dorf2 page.');
        }

        /** @var array<int, ParsedVillageSlot> $slotsById */
        $slotsById = [];

        foreach ($slotNodes as $slotNode) {
            if (! $slotNode instanceof DOMElement) {
                continue;
            }

            $slotId = (int) $slotNode->getAttribute('data-aid');

            if ($slotId < 19 || $slotId > 40) {
                continue;
            }

            $buildingGid = (int) $slotNode->getAttribute('data-gid');
            $name = trim((string) $slotNode->getAttribute('data-name'));
            $anchorNode = $xpath->query('.//a[1]', $slotNode)?->item(0);
            $levelLabelNode = $xpath->query(".//div[contains(@class, 'labelLayer')]", $slotNode)?->item(0);
            $currentLevel = 0;

            if ($anchorNode instanceof DOMElement) {
                $currentLevel = (int) ($anchorNode->getAttribute('data-level') ?: 0);
            }

            if ($currentLevel === 0 && $levelLabelNode instanceof DOMElement) {
                $currentLevel = $this->extractInteger($levelLabelNode->textContent) ?? 0;
            }

            $candidateSlot = new ParsedVillageSlot(
                slotId: $slotId,
                buildingGid: $buildingGid,
                buildingName: $name !== '' ? $name : TravianBuildingCatalog::nameForGid($buildingGid),
                currentLevel: $currentLevel,
                kind: 'building',
                isEmpty: $buildingGid === 0 || ($anchorNode instanceof DOMElement && str_contains($anchorNode->getAttribute('class'), 'emptyBuildingSlot')),
            );

            if (! isset($slotsById[$slotId]) || $this->shouldReplaceSlot($slotsById[$slotId], $candidateSlot)) {
                $slotsById[$slotId] = $candidateSlot;
            }
        }

        ksort($slotsById);

        return new ParsedDorf2Overview(
            buildingSlots: array_values($slotsById),
        );
    }

    /**
     * Decide whether the new slot variant is more complete than the stored one.
     */
    protected function shouldReplaceSlot(ParsedVillageSlot $currentSlot, ParsedVillageSlot $candidateSlot): bool
    {
        if ($currentSlot->buildingGid === 0 && $candidateSlot->buildingGid !== 0) {
            return true;
        }

        if ($currentSlot->buildingName === null && $candidateSlot->buildingName !== null) {
            return true;
        }

        return $currentSlot->currentLevel === 0 && $candidateSlot->currentLevel > 0;
    }

    /**
     * Extract the first integer from UI text.
     */
    protected function extractInteger(string $value): ?int
    {
        if (! preg_match('/-?\d+/', preg_replace('/[^\d-]+/u', ' ', $value) ?? '', $matches)) {
            return null;
        }

        return (int) $matches[0];
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
