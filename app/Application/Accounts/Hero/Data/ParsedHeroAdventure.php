<?php

namespace App\Application\Accounts\Hero\Data;

/**
 * Represents one available hero adventure.
 */
final readonly class ParsedHeroAdventure
{
    /**
     * Create a parsed adventure row.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $number,
        public ?string $place,
        public ?int $difficulty,
        public ?int $travelingDuration,
        public array $payload = [],
    ) {}
}
