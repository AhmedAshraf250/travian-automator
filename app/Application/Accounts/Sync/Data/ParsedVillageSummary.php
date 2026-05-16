<?php

namespace App\Application\Accounts\Sync\Data;

/**
 * Represents the core identifying data of a village discovered on overview pages.
 */
final readonly class ParsedVillageSummary
{
    /**
     * Create a parsed village summary.
     */
    public function __construct(
        public string $travianVillageId,
        public string $name,
        public ?int $x,
        public ?int $y,
        public ?int $population,
        public bool $isActive,
        public ?string $switchUri = null,
    ) {}
}
