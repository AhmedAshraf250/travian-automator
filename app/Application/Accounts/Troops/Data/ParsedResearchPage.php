<?php

namespace App\Application\Accounts\Troops\Data;

final readonly class ParsedResearchPage
{
    /**
     * @param  list<ParsedResearchUnit>  $units
     * @param  list<ParsedTroopQueueEntry>  $queue
     */
    public function __construct(
        public array $units,
        public array $queue,
    ) {}
}
