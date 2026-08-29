<?php

namespace App\Application\Accounts\Celebrations\Data;

use App\Enums\VillageCelebrationType;

/**
 * Represents one celebration option parsed from the town hall page.
 */
final readonly class ParsedCelebrationOption
{
    /**
     * Create a parsed celebration option DTO.
     */
    public function __construct(
        public VillageCelebrationType $type,
        public int $culturePoints,
        public ?string $actionUri,
        /** @var array{wood: int, clay: int, iron: int, crop: int} */
        public array $cost,
        public ?string $serverMessage,
    ) {}
}
