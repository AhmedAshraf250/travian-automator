<?php

namespace App\Application\Accounts\Hero\Data;

/**
 * Represents the hero attributes API payload.
 */
final readonly class HeroAttributesAnalysis
{
    /**
     * Create a hero attributes analysis.
     *
     * @param  array{wood?: int, clay?: int, iron?: int, crop?: int, crop_consumption?: int}  $reviveRequiredResources
     * @param  array<string, int>  $attributeUsedPoints
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public ?ParsedHeroState $heroState,
        public int $freePoints,
        public array $attributeUsedPoints,
        public bool $canReviveWithResources,
        public array $reviveRequiredResources,
        public ?int $reviveDurationSeconds,
        public ?string $reviveDurationLabel,
        public ?string $reviveBlockedMessage,
        public array $payload = [],
    ) {}
}
