<?php

namespace App\Application\Accounts\Sync\Data;

use Carbon\CarbonImmutable;

/**
 * Represents the synced runtime snapshot for village troops and movements.
 */
final readonly class ParsedVillageRuntimeState
{
    /**
     * Create a parsed runtime snapshot.
     *
     * @param  list<int>  $troopSlots
     * @param  list<ParsedVillageMovementEntry>  $movementEntries
     * @param  list<ParsedConstructionQueueEntry>  $constructionEntries
     */
    public function __construct(
        public ?int $tribeId,
        public array $troopSlots,
        public int $incomingAttackCount,
        public int $incomingReinforcementCount,
        public int $outgoingMovementCount,
        public array $movementEntries,
        public array $constructionEntries,
        public ?string $heroStatus,
        public ?int $heroRemainingSeconds,
        public ?CarbonImmutable $serverReportedAt = null,
    ) {}
}
