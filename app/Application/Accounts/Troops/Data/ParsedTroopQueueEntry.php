<?php

namespace App\Application\Accounts\Troops\Data;

final readonly class ParsedTroopQueueEntry
{
    public function __construct(
        public int $unitId,
        public int $quantity,
        public int $remainingSeconds,
        public ?int $targetLevel = null,
    ) {}
}
