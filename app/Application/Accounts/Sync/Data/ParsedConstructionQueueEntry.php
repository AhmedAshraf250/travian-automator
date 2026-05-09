<?php

namespace App\Application\Accounts\Sync\Data;

/**
 * Represents one active construction entry parsed from the village overview queue.
 */
final readonly class ParsedConstructionQueueEntry
{
    /**
     * Create a parsed construction queue entry.
     */
    public function __construct(
        public string $buildingName,
        public int $targetLevel,
        public int $remainingSeconds,
        public ?string $remainingLabel,
        public ?string $finishLabel,
    ) {}
}
