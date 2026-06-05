<?php

namespace App\Application\Accounts\Hero\Data;

/**
 * Represents the account-level hero state parsed from a Travian page or API.
 */
final readonly class ParsedHeroState
{
    /**
     * Create a parsed hero state.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public ?string $status,
        public ?float $healthPercent,
        public ?int $experiencePercent,
        public ?int $level,
        public int $adventuresAvailableCount,
        public bool $hasUnspentAttributePoints,
        public ?int $unspentAttributePoints,
        public ?int $heroRemainingSeconds,
        public ?string $homeVillageTravianId,
        public ?string $heroUri = null,
        public ?string $adventuresUri = null,
        public array $payload = [],
    ) {}
}
