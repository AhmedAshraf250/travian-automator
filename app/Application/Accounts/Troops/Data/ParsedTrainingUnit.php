<?php

namespace App\Application\Accounts\Troops\Data;

final readonly class ParsedTrainingUnit
{
    /** @param array{wood: int, clay: int, iron: int, crop: int} $cost */
    public function __construct(
        public int $unitId,
        public int $tribeSlot,
        public string $inputName,
        public int $smithyLevel,
        public int $maxTrainable,
        public array $cost,
        public int $cropUpkeep,
        public int $durationSeconds,
        public ?string $serverMessage,
    ) {}
}
