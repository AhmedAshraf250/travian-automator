<?php

namespace App\Application\Travian\Data;

/**
 * Describes whether a building can be constructed in a village from local rules.
 */
class BuildingEligibility
{
    /**
     * Create a building eligibility result.
     *
     * @param  list<array{gid: int, name: string|null, required_level: int, current_level: int}>  $missingRequirements
     * @param  array{wood?: int, clay?: int, iron?: int, crop?: int, crop_consumption?: int, total_resources?: int}|null  $requiredResources
     */
    public function __construct(
        public bool $allowed,
        public ?string $blockedReason,
        public array $missingRequirements,
        public ?array $requiredResources,
    ) {}
}
