<?php

namespace App\Application\Accounts\Sync\Data;

/**
 * Represents parsed resource values for the active village on a dorf1 page.
 */
final readonly class ParsedVillageResourceState
{
    /**
     * Create a parsed village resource snapshot.
     */
    public function __construct(
        public int $wood,
        public int $clay,
        public int $iron,
        public int $crop,
        public int $woodProduction,
        public int $clayProduction,
        public int $ironProduction,
        public int $cropProduction,
        public int $freeCropProduction,
        public int $warehouseCapacity,
        public int $granaryCapacity,
    ) {}
}
