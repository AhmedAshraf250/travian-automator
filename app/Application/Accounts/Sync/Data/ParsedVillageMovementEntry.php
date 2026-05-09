<?php

namespace App\Application\Accounts\Sync\Data;

/**
 * Represents a single movement line parsed from the village overview infobox.
 */
final readonly class ParsedVillageMovementEntry
{
    /**
     * Create a parsed movement entry.
     */
    public function __construct(
        public string $kind,
        public string $label,
        public int $count,
        public ?int $remainingSeconds,
        public ?string $remainingLabel,
    ) {}
}
