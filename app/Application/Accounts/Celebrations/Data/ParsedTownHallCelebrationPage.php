<?php

namespace App\Application\Accounts\Celebrations\Data;

/**
 * Represents the actionable celebration data parsed from the town hall page.
 */
final readonly class ParsedTownHallCelebrationPage
{
    /**
     * Create a parsed town hall celebration page DTO.
     *
     * @param  list<ParsedCelebrationOption>  $options
     */
    public function __construct(
        public array $options,
        public bool $hasRunningCelebration,
    ) {}
}
