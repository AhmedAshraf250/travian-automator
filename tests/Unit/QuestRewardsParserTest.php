<?php

use App\Application\Accounts\Rewards\Parsers\QuestRewardsParser;

function questRewardsTask08Fixture(string $path): string
{
    return file_get_contents(base_path('may-help/travian-samples/task08/'.$path));
}

test('parser detects the dorf1 progressive task reward indicator', function () {
    $parser = app(QuestRewardsParser::class);

    expect($parser->hasCollectableRewardIndicator(questRewardsTask08Fixture('shape-element.md')))->toBeTrue()
        ->and($parser->hasCollectableRewardIndicator('<html><body></body></html>'))->toBeFalse();
});

test('parser extracts collectable rewards from the tasks page react payload', function () {
    $rewards = app(QuestRewardsParser::class)->parseCollectableRewards(
        questRewardsTask08Fixture('step01-task.md'),
    );

    $populationReward = collect($rewards)->firstWhere('questType', 'populationProgressInVillage');
    $buildingReward = collect($rewards)->first(
        static fn ($reward): bool => $reward->questType === 'buildingProgress' && $reward->buildingId === 17,
    );

    expect($populationReward)->not->toBeNull()
        ->and($populationReward->collectionPayload())->toBe([
            'questType' => 'populationProgressInVillage',
            'scope' => 'settledVillage',
            'targetLevel' => 1,
            'heroLevel' => 7,
        ])
        ->and($buildingReward)->not->toBeNull()
        ->and($buildingReward->collectionPayload())->toBe([
            'questType' => 'buildingProgress',
            'scope' => 'settledVillage',
            'targetLevel' => 1,
            'heroLevel' => 7,
            'buildingId' => 17,
        ]);
});

test('parser extracts collectable rewards from reload json copied from devtools', function () {
    $rewards = app(QuestRewardsParser::class)->parseCollectableRewards(
        questRewardsTask08Fixture('step02-reload.md'),
    );

    expect($rewards)->not->toBeEmpty()
        ->and(collect($rewards)->contains(
            static fn ($reward): bool => $reward->questType === 'buildingProgress' && $reward->buildingId === 15,
        ))->toBeTrue();
});
