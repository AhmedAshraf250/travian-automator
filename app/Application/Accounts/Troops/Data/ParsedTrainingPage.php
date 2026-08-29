<?php

namespace App\Application\Accounts\Troops\Data;

final readonly class ParsedTrainingPage
{
    /**
     * @param  array<string, string>  $hiddenFields
     * @param  list<ParsedTrainingUnit>  $units
     * @param  list<ParsedTroopQueueEntry>  $queue
     */
    public function __construct(
        public ?string $actionUri,
        public array $hiddenFields,
        public array $units,
        public array $queue,
    ) {}
}
