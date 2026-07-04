<?php

namespace App\Application\Accounts\Construction\Parsers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses the Main Building page demolition controls.
 */
class MainBuildingDemolitionParser
{
    /**
     * @return array{
     *     main_building_level: int|null,
     *     available_buildings: list<array{slot_id:int,name:string,level:int}>,
     *     active: array{name:string,target_level:int|null,remaining_seconds:int|null,remaining_label:string|null,finish_label:string|null,cancel_uri:string|null}|null
     * }
     */
    public function parse(string $html): array
    {
        $xpath = $this->createXPath($html);

        return [
            'main_building_level' => $this->extractMainBuildingLevel($xpath),
            'available_buildings' => $this->extractAvailableBuildings($xpath),
            'active' => $this->extractActiveDemolition($xpath),
        ];
    }

    protected function extractMainBuildingLevel(DOMXPath $xpath): ?int
    {
        $heading = $xpath->query('//h1 | //h2')?->item(0);

        if ($heading instanceof DOMElement) {
            $level = $this->extractLastInteger($heading->textContent);

            if ($level !== null) {
                return $level;
            }
        }

        $mainBuildingOption = $xpath->query("//select[@id='demolish' or @name='abriss']//option[contains(., 'المبنى الرئيسي') or contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'main building')]")?->item(0);

        return $mainBuildingOption instanceof DOMElement
            ? $this->extractLastInteger($mainBuildingOption->textContent)
            : null;
    }

    /**
     * @return list<array{slot_id:int,name:string,level:int}>
     */
    protected function extractAvailableBuildings(DOMXPath $xpath): array
    {
        $buildings = [];

        foreach ($xpath->query("//select[@id='demolish' or @name='abriss']/option[@value]") ?: [] as $option) {
            if (! $option instanceof DOMElement) {
                continue;
            }

            $slotId = (int) $option->getAttribute('value');

            if ($slotId < 19 || $slotId > 40) {
                continue;
            }

            $label = trim(preg_replace('/\s+/u', ' ', html_entity_decode($option->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            $level = $this->extractLastInteger($label) ?? 0;
            $name = trim(preg_replace('/^\d+\s+|\s+\d+\s*$/u', '', $label) ?? '');

            $buildings[] = [
                'slot_id' => $slotId,
                'name' => $name !== '' ? $name : 'Building',
                'level' => $level,
            ];
        }

        return $buildings;
    }

    /**
     * @return array{name:string,target_level:int|null,remaining_seconds:int|null,remaining_label:string|null,finish_label:string|null,cancel_uri:string|null}|null
     */
    protected function extractActiveDemolition(DOMXPath $xpath): ?array
    {
        $row = $xpath->query("//table[@id='demolish']//tr[1]")?->item(0);

        if (! $row instanceof DOMElement) {
            return null;
        }

        $cancelUri = $this->extractCancelUri($xpath, $row);
        $timer = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' timer ')]", $row)?->item(0);
        $labelCell = $xpath->query(".//td[not(contains(@class, 'abort')) and not(contains(@class, 'times'))][1]", $row)?->item(0);
        $label = $labelCell instanceof DOMElement ? trim(preg_replace('/\s+/u', ' ', $labelCell->textContent) ?? '') : 'Building';
        $targetLevel = $this->extractLastInteger($label);
        $name = $targetLevel !== null
            ? trim(preg_replace('/\s*(?:المستوى|level)\s*\d+/iu', '', $label) ?? '')
            : $label;

        return [
            'name' => $name !== '' ? $name : 'Building',
            'target_level' => $targetLevel,
            'remaining_seconds' => $timer instanceof DOMElement ? max(0, (int) $timer->getAttribute('value')) : null,
            'remaining_label' => $timer instanceof DOMElement ? trim($timer->textContent) : null,
            'finish_label' => $this->extractFinishLabel($row->textContent),
            'cancel_uri' => $cancelUri,
        ];
    }

    protected function extractCancelUri(DOMXPath $xpath, DOMElement $row): ?string
    {
        $cancelButton = $xpath->query(".//button[contains(@onclick, 'del=')]", $row)?->item(0);

        if (! $cancelButton instanceof DOMElement) {
            return null;
        }

        $onclick = html_entity_decode($cancelButton->getAttribute('onclick'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match("/window\.location\.href\s*=\s*'([^']+)'/u", $onclick, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function extractLastInteger(string $value): ?int
    {
        if (preg_match_all('/\d+/u', $this->normalizeUnicodeDigits($value), $matches) === false || $matches[0] === []) {
            return null;
        }

        return (int) end($matches[0]);
    }

    protected function extractFinishLabel(string $value): ?string
    {
        if (preg_match_all('/\d{1,2}:\d{2}/u', $this->normalizeUnicodeDigits($value), $matches) === false || $matches[0] === []) {
            return null;
        }

        return end($matches[0]) ?: null;
    }

    protected function normalizeUnicodeDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);
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
