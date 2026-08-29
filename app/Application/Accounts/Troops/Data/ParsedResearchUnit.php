<?php

namespace App\Application\Accounts\Troops\Data;

final readonly class ParsedResearchUnit
{
    /**
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $cost
     * @param  list<array{gid: int, required_level: int, current_level: int|null, met: bool}>  $requirements
     */
    public function __construct(
        public int $unitId,
        public ?int $currentLevel,
        public array $cost,
        public int $durationSeconds,
        public ?string $actionUri,
        public array $requirements,
        public ?string $serverMessage,
        public bool $hasResourceShortage,
    ) {}
}
