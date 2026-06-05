<?php

namespace App\Application\Accounts\Construction\Data;

/**
 * Represents the actionable state parsed from a Travian build.php document.
 */
class BuildPageAnalysis
{
    /**
     * Create a build page analysis instance.
     *
     * @param  array{wood?: int, clay?: int, iron?: int, crop?: int, crop_consumption?: int}  $requiredResources
     * @param  array<int, array<string, mixed>>  $availableBuildings
     * @param  array<int, array<string, mixed>>  $blockedBuildings
     * @param  list<array{gid: int|null, name: string|null, required_level: int|null, current_level: int|null}>  $missingRequirements
     * @param  array<int, string>  $targetGidActionUris
     */
    public function __construct(
        public ?string $actionUri,
        public array $requiredResources,
        public ?string $blockedReason,
        public ?string $blockedMessage,
        public ?int $resourceReadySeconds,
        public ?string $resourceReadyLabel,
        public array $availableBuildings = [],
        public array $blockedBuildings = [],
        public array $missingRequirements = [],
        public ?int $activeCategory = null,
        public array $targetGidActionUris = [],
    ) {}

    /**
     * Determine whether this page exposes a normal construction action.
     */
    public function hasAction(): bool
    {
        return $this->actionUri !== null && $this->actionUri !== '';
    }

    /**
     * Determine whether construction is blocked only by missing resources.
     */
    public function isResourceShortage(): bool
    {
        return $this->blockedReason === 'resource_shortage';
    }
}
