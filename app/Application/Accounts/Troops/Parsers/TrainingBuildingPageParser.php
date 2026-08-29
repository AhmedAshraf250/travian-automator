<?php

namespace App\Application\Accounts\Troops\Parsers;

use App\Application\Accounts\Troops\Data\ParsedTrainingPage;
use App\Application\Accounts\Troops\Data\ParsedTrainingUnit;
use App\Application\Accounts\Troops\Data\ParsedTroopQueueEntry;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class TrainingBuildingPageParser
{
    public function parse(string $html): ParsedTrainingPage
    {
        $xpath = $this->createXPath($html);
        $form = $xpath->query("//form[.//input[@name='action' and @value='trainTroops']]")?->item(0);
        $hiddenFields = [];
        $units = [];

        if ($form instanceof DOMElement) {
            foreach ($xpath->query(".//input[@type='hidden' and @name]", $form) ?: [] as $input) {
                if ($input instanceof DOMElement) {
                    $hiddenFields[$input->getAttribute('name')] = $input->getAttribute('value');
                }
            }

            foreach ($xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' action ') and contains(concat(' ', normalize-space(@class), ' '), ' troop ') and not(contains(concat(' ', normalize-space(@class), ' '), ' empty '))]", $form) ?: [] as $unitNode) {
                if (! $unitNode instanceof DOMElement) {
                    continue;
                }

                $unit = $this->parseUnit($xpath, $unitNode);

                if ($unit instanceof ParsedTrainingUnit) {
                    $units[$unit->unitId] = $unit;
                }
            }
        }

        return new ParsedTrainingPage(
            actionUri: $form instanceof DOMElement ? html_entity_decode($form->getAttribute('action'), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            hiddenFields: $hiddenFields,
            units: array_values($units),
            queue: $this->parseQueue($xpath),
        );
    }

    protected function parseUnit(DOMXPath $xpath, DOMElement $unitNode): ?ParsedTrainingUnit
    {
        $wrapper = $xpath->query('.//*[@data-troopid or @data-troopID]', $unitNode)?->item(0);
        $input = $xpath->query(".//input[starts-with(@name, 't')]", $unitNode)?->item(0);
        $unitId = $this->extractUnitId($xpath, $unitNode);

        if (! $wrapper instanceof DOMElement || ! $input instanceof DOMElement || $unitId === null) {
            return null;
        }

        $tribeSlot = $this->extractInteger(
            $wrapper->getAttribute('data-troopid') ?: $wrapper->getAttribute('data-troopID'),
        ) ?? 0;
        $levelNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' level ')]", $unitNode)?->item(0);
        $durationNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' duration ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' value ')]", $unitNode)?->item(0);
        $messageNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' errorMessage ')]", $unitNode)?->item(0);

        return new ParsedTrainingUnit(
            unitId: $unitId,
            tribeSlot: $tribeSlot,
            inputName: $input->getAttribute('name'),
            smithyLevel: $levelNode instanceof DOMNode ? ($this->extractInteger($levelNode->textContent) ?? 0) : 0,
            maxTrainable: $this->extractMaxTrainable($unitNode),
            cost: $this->extractResources($xpath, $unitNode),
            cropUpkeep: $this->extractCropUpkeep($xpath, $unitNode),
            durationSeconds: $durationNode instanceof DOMNode ? $this->durationToSeconds($durationNode->textContent) : 0,
            serverMessage: $messageNode instanceof DOMNode ? trim($messageNode->textContent) : null,
        );
    }

    /** @return list<ParsedTroopQueueEntry> */
    protected function parseQueue(DOMXPath $xpath): array
    {
        $entries = [];

        foreach ($xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' under_progress ')]//tbody/tr[not(contains(concat(' ', normalize-space(@class), ' '), ' next '))]") ?: [] as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            $unitId = $this->extractUnitId($xpath, $row);
            $description = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' desc ')]", $row)?->item(0);
            $timer = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' dur ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' timer ')]", $row)?->item(0);

            if ($unitId === null || ! $description instanceof DOMNode) {
                continue;
            }

            $entries[] = new ParsedTroopQueueEntry(
                unitId: $unitId,
                quantity: $this->extractInteger($description->textContent) ?? 0,
                remainingSeconds: $timer instanceof DOMElement
                    ? ((int) $timer->getAttribute('value') ?: $this->durationToSeconds($timer->textContent))
                    : 0,
            );
        }

        return $entries;
    }

    protected function extractMaxTrainable(DOMElement $unitNode): int
    {
        $html = $unitNode->ownerDocument?->saveHTML($unitNode) ?: '';

        if (preg_match('/\.val\((\d+)\)/u', $html, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    /** @return array{wood: int, clay: int, iron: int, crop: int} */
    protected function extractResources(DOMXPath $xpath, DOMElement $node): array
    {
        $resources = ['wood' => 0, 'clay' => 0, 'iron' => 0, 'crop' => 0];
        $classMap = ['r1Big' => 'wood', 'r2Big' => 'clay', 'r3Big' => 'iron', 'r4Big' => 'crop'];

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
                    $resources[$key] = $this->extractInteger($value->textContent) ?? 0;
                }
            }
        }

        return $resources;
    }

    protected function extractCropUpkeep(DOMXPath $xpath, DOMElement $node): int
    {
        $icon = $xpath->query(".//i[contains(concat(' ', normalize-space(@class), ' '), ' cropConsumptionBig ')]", $node)?->item(0);
        $value = $icon instanceof DOMElement ? $xpath->query("./following-sibling::*[contains(concat(' ', normalize-space(@class), ' '), ' value ')][1]", $icon)?->item(0) : null;

        return $value instanceof DOMNode ? ($this->extractInteger($value->textContent) ?? 0) : 0;
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

    protected function extractInteger(string $value): ?int
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
