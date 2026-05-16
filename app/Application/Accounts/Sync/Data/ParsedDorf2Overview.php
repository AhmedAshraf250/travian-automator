<?php

namespace App\Application\Accounts\Sync\Data;

/**
 * Represents the structured building-slot layout parsed from a dorf2 page.
 */
final readonly class ParsedDorf2Overview
{
    /**
     * Create a parsed dorf2 overview DTO.
     *
     * @param  list<ParsedVillageSlot>  $buildingSlots
     */
    public function __construct(
        public array $buildingSlots,
    ) {}
}
