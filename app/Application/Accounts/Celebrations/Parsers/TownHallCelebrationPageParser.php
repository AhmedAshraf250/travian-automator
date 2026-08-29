<?php

namespace App\Application\Accounts\Celebrations\Parsers;

use App\Application\Accounts\Celebrations\Data\ParsedCelebrationOption;
use App\Application\Accounts\Celebrations\Data\ParsedTownHallCelebrationPage;
use App\Enums\VillageCelebrationType;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses the Travian town hall page for celebration options and actions.
 */
class TownHallCelebrationPageParser
{
    /**
     * Parse the celebration options from a town hall page.
     */
    public function parse(string $html): ParsedTownHallCelebrationPage
    {
        $xpath = $this->createXPath($html);
        $options = [];

        foreach ($xpath->query("//div[contains(@class, 'build_details') and contains(@class, 'researches')]//div[contains(@class, 'research')]") ?: [] as $researchNode) {
            if (! $researchNode instanceof DOMElement) {
                continue;
            }

            $titleNode = $xpath->query(".//div[contains(@class, 'title')]", $researchNode)?->item(0);
            $pointsNode = $xpath->query(".//span[contains(@class, 'points')]", $researchNode)?->item(0);

            if (! $titleNode instanceof DOMElement || ! $pointsNode instanceof DOMElement) {
                continue;
            }

            $type = $this->inferCelebrationType($titleNode->textContent);

            if ($type === null) {
                continue;
            }

            $options[] = new ParsedCelebrationOption(
                type: $type,
                culturePoints: $this->extractInteger($pointsNode->textContent) ?? 0,
                actionUri: $this->extractActionUri($html, $type, $researchNode),
                cost: $this->extractResources($xpath, $researchNode),
                serverMessage: $this->extractServerMessage($xpath, $researchNode),
            );
        }

        return new ParsedTownHallCelebrationPage(
            options: $options,
            hasRunningCelebration: $this->hasRunningCelebration($xpath),
        );
    }

    /**
     * Infer the celebration type from localized UI text.
     */
    protected function inferCelebrationType(string $title): ?VillageCelebrationType
    {
        $normalizedTitle = mb_strtolower(trim($title));

        return match (true) {
            str_contains($normalizedTitle, 'small'),
            str_contains($normalizedTitle, 'صغير') => VillageCelebrationType::Small,
            str_contains($normalizedTitle, 'great'),
            str_contains($normalizedTitle, 'كبير') => VillageCelebrationType::Great,
            default => null,
        };
    }

    /**
     * Detect whether the town hall already has a running celebration.
     */
    protected function hasRunningCelebration(DOMXPath $xpath): bool
    {
        return ($xpath->query("//table[contains(@class, 'under_progress')]//tbody/tr")?->length ?? 0) > 0
            || ($xpath->query("//*[contains(@class, 'errorMessage') and contains(normalize-space(.), 'احتفال')]")?->length ?? 0) > 0;
    }

    /**
     * Extract the action URI for a specific celebration type.
     */
    protected function extractActionUri(string $html, VillageCelebrationType $type, DOMElement $researchNode): ?string
    {
        $actionLink = $this->extractActionUriFromNode($researchNode);

        if ($actionLink !== null) {
            return $actionLink;
        }

        $actionNumber = $type === VillageCelebrationType::Great ? 2 : 1;

        if (preg_match('/(\/build\.php[^"\']*action=celebration(?:&amp;|&)do='.$actionNumber.'[^"\']*)/u', $html, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Extract the clickable action URI from the current research node.
     */
    protected function extractActionUriFromNode(DOMElement $researchNode): ?string
    {
        $nodeHtml = $researchNode->ownerDocument?->saveHTML($researchNode);

        if (! is_string($nodeHtml) || $nodeHtml === '') {
            return null;
        }

        if (preg_match('/(\/build\.php[^"\']*action=celebration[^"\']*)/u', $nodeHtml, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** @return array{wood: int, clay: int, iron: int, crop: int} */
    protected function extractResources(DOMXPath $xpath, DOMElement $node): array
    {
        $resources = ['wood' => 0, 'clay' => 0, 'iron' => 0, 'crop' => 0];
        $classMap = ['r1Big' => 'wood', 'r2Big' => 'clay', 'r3Big' => 'iron', 'r4Big' => 'crop'];

        foreach ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' resource ')]", $node) ?: [] as $resourceNode) {
            if (! $resourceNode instanceof DOMElement) {
                continue;
            }

            $icon = $xpath->query('.//i', $resourceNode)?->item(0);
            $value = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' value ')]", $resourceNode)?->item(0);

            if (! $icon instanceof DOMElement || $value === null) {
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

    protected function extractServerMessage(DOMXPath $xpath, DOMElement $node): ?string
    {
        $message = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' errorMessage ')]", $node)?->item(0);
        $value = trim((string) $message?->textContent);

        return $value === '' ? null : $value;
    }

    /**
     * Extract an integer value from mixed UI text.
     */
    protected function extractInteger(string $value): ?int
    {
        if (! preg_match('/\d+/u', preg_replace('/[^\d]+/u', ' ', $value) ?? '', $matches)) {
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
