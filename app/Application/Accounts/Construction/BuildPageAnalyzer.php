<?php

namespace App\Application\Accounts\Construction;

use App\Application\Accounts\Construction\Data\BuildPageAnalysis;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses Travian build.php pages into construction action or blocking metadata.
 */
class BuildPageAnalyzer
{
    /**
     * Analyze a Travian build page.
     */
    public function analyze(string $html, ?int $targetGid = null): BuildPageAnalysis
    {
        $xpath = $this->createXPath($html);
        $contracts = $this->extractBuildingContracts($xpath);
        $targetContract = $targetGid !== null ? ($contracts[$targetGid] ?? null) : null;
        $targetWrapper = $targetGid !== null ? $this->findContractWrapper($xpath, $targetGid) : null;
        $actionUri = $targetContract['action_uri'] ?? $this->extractActionUri($xpath, $targetGid);
        $blockedMessage = $this->extractBlockedMessage($xpath);
        $resourceReadySeconds = $this->extractResourceReadySeconds($xpath);
        $missingRequirements = is_array($targetContract)
            ? ($targetContract['missing_requirements'] ?? [])
            : [];
        $blockedReason = $this->extractBlockedReason(
            blockedMessage: $blockedMessage,
            resourceReadySeconds: $resourceReadySeconds,
            missingRequirements: $missingRequirements,
            hasAction: $actionUri !== null,
        );

        return new BuildPageAnalysis(
            actionUri: $actionUri,
            requiredResources: $this->extractRequiredResources($xpath, $targetWrapper),
            blockedReason: $blockedReason,
            blockedMessage: $blockedMessage,
            resourceReadySeconds: $resourceReadySeconds,
            resourceReadyLabel: $this->extractResourceReadyLabel($xpath),
            availableBuildings: array_filter(
                $contracts,
                static fn (array $contract): bool => ($contract['action_uri'] ?? null) !== null,
            ),
            blockedBuildings: array_filter(
                $contracts,
                static fn (array $contract): bool => ($contract['action_uri'] ?? null) === null,
            ),
            missingRequirements: $missingRequirements,
            activeCategory: $this->extractActiveCategory($xpath),
            targetGidActionUris: array_filter(
                array_map(static fn (array $contract): ?string => $contract['action_uri'] ?? null, $contracts),
                static fn (?string $actionUri): bool => $actionUri !== null,
            ),
        );
    }

    /**
     * Parse the first usable non-gold build action from a build page.
     */
    protected function extractActionUri(DOMXPath $xpath, ?int $targetGid): ?string
    {
        $targetWrapper = $targetGid !== null ? $this->findContractWrapper($xpath, $targetGid) : null;

        if ($targetWrapper instanceof DOMElement) {
            return $this->extractActionUriFromNode($xpath, $targetWrapper);
        }

        foreach ($xpath->query('//button[@onclick]') ?: [] as $buttonNode) {
            if (! $buttonNode instanceof DOMElement) {
                continue;
            }

            $onclick = html_entity_decode((string) $buttonNode->getAttribute('onclick'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (! str_contains($onclick, 'action=build')) {
                continue;
            }

            if (preg_match("/window\\.location\\.href\\s*=\\s*'([^']+)'/", $onclick, $matches) !== 1) {
                continue;
            }

            $actionUri = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (str_contains($actionUri, 'buildmaster')) {
                continue;
            }

            return $actionUri;
        }

        return null;
    }

    /**
     * Extract all visible building contracts from an empty-slot or building page.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractBuildingContracts(DOMXPath $xpath): array
    {
        $contracts = [];

        foreach ($xpath->query("//*[starts-with(@id, 'contract_building')]") ?: [] as $wrapper) {
            if (! $wrapper instanceof DOMElement) {
                continue;
            }

            if (preg_match('/contract_building(\d+)/', (string) $wrapper->getAttribute('id'), $matches) !== 1) {
                continue;
            }

            $gid = (int) $matches[1];
            $missingRequirements = $this->extractMissingRequirements($xpath, $wrapper);
            $actionUri = $this->extractActionUriFromNode($xpath, $wrapper);

            $contracts[$gid] = [
                'gid' => $gid,
                'name' => $this->extractContractName($xpath, $wrapper),
                'action_uri' => $actionUri,
                'required_resources' => $this->extractRequiredResources($xpath, $wrapper),
                'missing_requirements' => $missingRequirements,
                'blocked_reason' => $actionUri === null && $missingRequirements !== []
                    ? 'missing_requirements'
                    : null,
            ];
        }

        return $contracts;
    }

    /**
     * Find the wrapper for a specific contract building gid.
     */
    protected function findContractWrapper(DOMXPath $xpath, int $targetGid): ?DOMElement
    {
        $targetWrapper = $xpath->query("//*[@id='contract_building{$targetGid}']")?->item(0);

        return $targetWrapper instanceof DOMElement ? $targetWrapper : null;
    }

    /**
     * Parse the first usable section1 build action from a wrapper.
     */
    protected function extractActionUriFromNode(DOMXPath $xpath, DOMElement $context): ?string
    {
        $buttonNodes = iterator_to_array($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' section1 ')]//button[@onclick]", $context) ?: []);

        if ($buttonNodes === []) {
            $buttonNodes = iterator_to_array($xpath->query('.//button[@onclick]', $context) ?: []);
        }

        foreach ($buttonNodes as $buttonNode) {
            if (! $buttonNode instanceof DOMElement) {
                continue;
            }

            $onclick = html_entity_decode((string) $buttonNode->getAttribute('onclick'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (! str_contains($onclick, 'action=build')) {
                continue;
            }

            if (preg_match("/window\\.location\\.href\\s*=\\s*'([^']+)'/", $onclick, $matches) !== 1) {
                continue;
            }

            $actionUri = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (str_contains($actionUri, 'buildmaster')) {
                continue;
            }

            return $actionUri;
        }

        return null;
    }

    /**
     * Extract visible resource charges from the contract area.
     *
     * @return array{wood?: int, clay?: int, iron?: int, crop?: int, crop_consumption?: int}
     */
    protected function extractRequiredResources(DOMXPath $xpath, ?DOMElement $context = null): array
    {
        $resources = [];
        $resourceMap = [
            'r1Big' => 'wood',
            'r1' => 'wood',
            'r2Big' => 'clay',
            'r2' => 'clay',
            'r3Big' => 'iron',
            'r3' => 'iron',
            'r4Big' => 'crop',
            'r4' => 'crop',
            'cropConsumptionBig' => 'crop_consumption',
            'cropConsumption' => 'crop_consumption',
        ];

        $expression = ($context instanceof DOMElement ? './/*' : '//*')
            ."[contains(concat(' ', normalize-space(@class), ' '), ' resourceWrapper ') and contains(concat(' ', normalize-space(@class), ' '), ' charges ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' inlineIcon ')]";
        $nodes = $xpath->query($expression, $context) ?: [];

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $icon = $xpath->query('.//i', $node)?->item(0);
            $value = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' value ')]", $node)?->item(0);

            if (! $icon instanceof DOMElement || ! $value instanceof DOMElement) {
                continue;
            }

            $iconClass = (string) $icon->getAttribute('class');

            foreach ($resourceMap as $classNeedle => $resourceKey) {
                if (! str_contains($iconClass, $classNeedle)) {
                    continue;
                }

                $resources[$resourceKey] = $this->parseInteger($value->textContent);

                break;
            }
        }

        return $resources;
    }

    /**
     * Resolve the normalized blocking reason.
     *
     * @param  list<array<string, mixed>>  $missingRequirements
     */
    protected function extractBlockedReason(?string $blockedMessage, ?int $resourceReadySeconds, array $missingRequirements, bool $hasAction): ?string
    {
        if ($hasAction) {
            return null;
        }

        if ($resourceReadySeconds !== null) {
            return 'resource_shortage';
        }

        if ($blockedMessage !== null && $this->isCropFieldRequiredMessage($blockedMessage)) {
            return 'crop_field_required';
        }

        if ($missingRequirements !== []) {
            return 'missing_requirements';
        }

        if ($blockedMessage === null) {
            return null;
        }

        return 'blocked';
    }

    /**
     * Detect Travian's free-crop blocker that specifically asks for crop fields.
     */
    protected function isCropFieldRequiredMessage(string $blockedMessage): bool
    {
        return str_contains($blockedMessage, 'نقص الغذاء')
            || str_contains($blockedMessage, 'حقل القمح')
            || str_contains(mb_strtolower($blockedMessage), 'crop field');
    }

    /**
     * Extract the visible build blocking message.
     */
    protected function extractBlockedMessage(DOMXPath $xpath): ?string
    {
        $node = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' errorMessage ')]")?->item(0);

        if (! $node instanceof DOMElement) {
            return null;
        }

        $message = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');

        return $message !== '' ? $message : null;
    }

    /**
     * Extract the countdown seconds until resources become available.
     */
    protected function extractResourceReadySeconds(DOMXPath $xpath): ?int
    {
        $timer = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' errorMessage ')]//span[contains(concat(' ', normalize-space(@class), ' '), ' timer ') and @counting='down' and @value]")?->item(0);

        if (! $timer instanceof DOMElement) {
            return null;
        }

        return max(0, (int) $timer->getAttribute('value'));
    }

    /**
     * Extract the human-readable countdown label.
     */
    protected function extractResourceReadyLabel(DOMXPath $xpath): ?string
    {
        $timer = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' errorMessage ')]//span[contains(concat(' ', normalize-space(@class), ' '), ' timer ') and @counting='down']")?->item(0);

        if (! $timer instanceof DOMElement) {
            return null;
        }

        $label = trim($timer->textContent);

        return $label !== '' ? $label : null;
    }

    /**
     * Extract the active empty-slot building category.
     */
    protected function extractActiveCategory(DOMXPath $xpath): ?int
    {
        $activeTab = $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' tabItem ') and contains(concat(' ', normalize-space(@class), ' '), ' active ')][@href]")?->item(0);

        if (! $activeTab instanceof DOMElement) {
            return null;
        }

        $href = html_entity_decode((string) $activeTab->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/(?:\?|&)category=(\d+)/', $href, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Extract a building name from a contract wrapper.
     */
    protected function extractContractName(DOMXPath $xpath, DOMElement $context): ?string
    {
        $heading = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' build_desc ')]//h2", $context)?->item(0);

        if (! $heading instanceof DOMElement) {
            return null;
        }

        $name = trim(preg_replace('/^\s*\d+\.\s*/u', '', $heading->textContent) ?? '');

        return $name !== '' ? $name : null;
    }

    /**
     * Extract unmet prerequisite rows from one contract wrapper.
     *
     * @return list<array{gid: int|null, name: string|null, required_level: int|null, current_level: int|null}>
     */
    protected function extractMissingRequirements(DOMXPath $xpath, DOMElement $context): array
    {
        $requirements = [];

        foreach ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' buildingCondition ') and contains(concat(' ', normalize-space(@class), ' '), ' error ')]", $context) ?: [] as $condition) {
            if (! $condition instanceof DOMElement) {
                continue;
            }

            $link = $xpath->query('.//a', $condition)?->item(0);
            $level = $xpath->query('.//span', $condition)?->item(0);
            $gid = null;

            if ($link instanceof DOMElement && preg_match("/building',\\s*(\\d+)/", (string) $link->getAttribute('onclick'), $matches) === 1) {
                $gid = (int) $matches[1];
            }

            $requirements[] = [
                'gid' => $gid,
                'name' => $link instanceof DOMElement ? trim($link->textContent) : null,
                'required_level' => $level instanceof DOMElement ? $this->extractInteger($level->textContent) : null,
                'current_level' => $level instanceof DOMElement ? $this->extractInteger((string) $level->getAttribute('title')) : null,
            ];
        }

        return $requirements;
    }

    /**
     * Parse Travian resource numbers that may include separators or bidi marks.
     */
    protected function parseInteger(string $value): int
    {
        $normalized = preg_replace('/[^\d]/u', '', $value) ?? '';

        return (int) $normalized;
    }

    /**
     * Extract the first integer from a mixed label.
     */
    protected function extractInteger(string $value): ?int
    {
        $normalized = preg_replace('/[^\d]/u', '', $value) ?? '';

        return $normalized !== '' ? (int) $normalized : null;
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
