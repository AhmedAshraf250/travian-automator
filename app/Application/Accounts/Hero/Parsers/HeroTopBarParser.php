<?php

namespace App\Application\Accounts\Hero\Parsers;

use App\Application\Accounts\Hero\Data\ParsedHeroState;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses the top-bar hero widget that appears on normal Travian documents.
 */
class HeroTopBarParser
{
    /**
     * Parse the hero top bar from an authenticated Travian document.
     */
    public function parse(string $html): ?ParsedHeroState
    {
        $xpath = $this->createXPath($html);
        $container = $xpath->query("//*[@id='topBarHero']")?->item(0);

        if (! $container instanceof DOMElement) {
            return null;
        }

        $heroUri = $this->hrefFromFirst($xpath, ".//*[@id='heroImageButton'][@href]", $container);
        $adventuresUri = $this->hrefFromFirst($xpath, ".//a[contains(concat(' ', normalize-space(@class), ' '), ' adventure ')][@href]", $container);
        $statusNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' heroStatus ')]", $container)?->item(0);
        $statusTitle = $statusNode instanceof DOMElement
            ? html_entity_decode((string) $statusNode->getAttribute('title'), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : '';

        return new ParsedHeroState(
            status: $this->parseStatus($xpath, $container, $statusTitle),
            healthPercent: $this->parsePercentTitle($xpath, 'health', $container),
            experiencePercent: $this->parsePercentTitle($xpath, 'experience', $container),
            level: null,
            adventuresAvailableCount: $this->parseAdventureCount($xpath, $container),
            hasUnspentAttributePoints: $this->hasVisibleLevelUp($xpath, $container),
            unspentAttributePoints: null,
            heroRemainingSeconds: $this->parseRemainingSeconds($xpath, $container, $statusTitle),
            homeVillageTravianId: $this->parseHomeVillageId($xpath, $container),
            heroUri: $heroUri,
            adventuresUri: $adventuresUri,
            payload: [
                'source' => 'top_bar',
                'status_title' => $statusTitle,
            ],
        );
    }

    /**
     * Infer the visible hero status.
     */
    protected function parseStatus(DOMXPath $xpath, DOMElement $container, string $statusTitle): string
    {
        if ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' heroDead ')]", $container)?->length > 0) {
            return 'dead';
        }

        if ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' heroReviving ')]", $container)?->length > 0) {
            return 'regenerating';
        }

        if ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' heroRunning ')]", $container)?->length > 0) {
            $normalizedTitle = mb_strtolower($statusTitle);

            return str_contains($normalizedTitle, 'adventure') || str_contains($normalizedTitle, 'مغام')
                ? 'adventure'
                : 'returning';
        }

        if ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' heroHome ')]", $container)?->length > 0) {
            return 'home';
        }

        return 'unknown';
    }

    /**
     * Extract the health or experience percentage from the SVG title.
     */
    protected function parsePercentTitle(DOMXPath $xpath, string $svgClass, DOMElement $container): ?int
    {
        $title = $xpath->query(".//*[name()='svg' and contains(concat(' ', normalize-space(@class), ' '), ' {$svgClass} ')]//*[name()='path' and contains(concat(' ', normalize-space(@class), ' '), ' title ')]/*[name()='title']", $container)?->item(0);

        if (! $title instanceof DOMElement) {
            return null;
        }

        return $this->extractInteger(html_entity_decode($title->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Extract the available adventure counter.
     */
    protected function parseAdventureCount(DOMXPath $xpath, DOMElement $container): int
    {
        $node = $xpath->query(".//a[contains(concat(' ', normalize-space(@class), ' '), ' adventure ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' content ')]", $container)?->item(0);

        if (! $node instanceof DOMElement) {
            return 0;
        }

        return $this->extractInteger($node->textContent) ?? 0;
    }

    /**
     * Determine whether the level-up marker is visibly active.
     */
    protected function hasVisibleLevelUp(DOMXPath $xpath, DOMElement $container): bool
    {
        $node = $xpath->query(".//i[contains(concat(' ', normalize-space(@class), ' '), ' levelUp ')]", $container)?->item(0);

        return $node instanceof DOMElement
            && str_contains(' '.$node->getAttribute('class').' ', ' show ');
    }

    /**
     * Extract a hero movement countdown if present.
     */
    protected function parseRemainingSeconds(DOMXPath $xpath, DOMElement $container, string $statusTitle): ?int
    {
        $timer = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' timer ') and @value]", $container)?->item(0);

        if ($timer instanceof DOMElement) {
            return (int) $timer->getAttribute('value');
        }

        if (preg_match('/value=["\']?(\d+)/u', $statusTitle, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract the home village id from the hero home link.
     */
    protected function parseHomeVillageId(DOMXPath $xpath, DOMElement $container): ?string
    {
        $homeLink = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' heroHome ')]/ancestor::a[@href][1]", $container)?->item(0);

        if (! $homeLink instanceof DOMElement) {
            return null;
        }

        $href = html_entity_decode((string) $homeLink->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/(?:\?|&)newdid=([^&]+)/', $href, $matches) !== 1) {
            return null;
        }

        return urldecode($matches[1]);
    }

    /**
     * Get the first href matching the XPath query.
     */
    protected function hrefFromFirst(DOMXPath $xpath, string $query, DOMElement $container): ?string
    {
        $node = $xpath->query($query, $container)?->item(0);

        if (! $node instanceof DOMElement) {
            return null;
        }

        $href = trim(html_entity_decode((string) $node->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $href !== '' ? $href : null;
    }

    /**
     * Extract the first integer from localized text.
     */
    protected function extractInteger(string $value): ?int
    {
        $normalized = preg_replace('/[^\d-]+/u', ' ', $value) ?? '';

        if (preg_match('/-?\d+/', $normalized, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    /**
     * Create an XPath parser for the given document.
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
