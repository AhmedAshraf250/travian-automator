<?php

namespace App\Application\Accounts\Hero\Data;

/**
 * Represents the actionable state parsed from /hero/adventures.
 */
final readonly class HeroAdventurePageAnalysis
{
    /**
     * Create an adventure page analysis.
     *
     * @param  list<ParsedHeroAdventure>  $adventures
     */
    public function __construct(
        public array $adventures,
        public ?ParsedHeroState $heroState = null,
    ) {}

    /**
     * Return the first adventure in Travian's own page order.
     */
    public function firstAdventure(): ?ParsedHeroAdventure
    {
        return $this->adventures[0] ?? null;
    }
}
