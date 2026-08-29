<?php

namespace App\Application\Accounts\Troops\Data;

use App\Models\VillageTroopSnapshot;

final readonly class RefreshedVillageTroopState
{
    /** @param array<int, array{page: ParsedTrainingPage, effective_uri: string}> $trainingPages */
    public function __construct(
        public VillageTroopSnapshot $snapshot,
        public array $trainingPages,
        public ?ParsedResearchPage $academyPage,
        public ?string $academyEffectiveUri,
        public ?ParsedResearchPage $smithyPage,
        public ?string $smithyEffectiveUri,
    ) {}
}
