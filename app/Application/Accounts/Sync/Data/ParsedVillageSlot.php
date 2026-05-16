<?php

namespace App\Application\Accounts\Sync\Data;

/**
 * Represents one parsed dorf slot, either a resource field or a village building position.
 */
final readonly class ParsedVillageSlot
{
    /**
     * Create a parsed slot DTO.
     */
    public function __construct(
        public int $slotId,
        public int $buildingGid,
        public ?string $buildingName,
        public int $currentLevel,
        public string $kind,
        public bool $isEmpty = false,
    ) {}
}
