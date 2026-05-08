<?php

namespace App\Application\Accounts\Sync\Data;

/**
 * Represents the useful structured information parsed from a dorf1 overview page.
 */
final readonly class ParsedDorf1Overview
{
    /**
     * Create a parsed dorf1 overview DTO.
     *
     * @param  list<ParsedVillageSummary>  $villages
     */
    public function __construct(
        public ParsedVillageSummary $activeVillage,
        public ParsedVillageResourceState $resourceState,
        public array $villages,
    ) {}
}
