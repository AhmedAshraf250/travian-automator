<?php

use App\Application\Accounts\Rewards\Parsers\DailyQuestRewardsParser;

function dailyQuestRewardsTask09Fixture(string $path): string
{
    return file_get_contents(base_path('may-help/travian-samples/task09/'.$path));
}

test('parser detects only the daily quest reward indicator', function () {
    $parser = app(DailyQuestRewardsParser::class);

    expect($parser->hasCollectableRewardIndicator(dailyQuestRewardsTask09Fixture('normal-dorf1-response.md')))->toBeTrue()
        ->and($parser->hasCollectableRewardIndicator(dailyQuestRewardsTask09Fixture('daily-quest-element.md')))->toBeTrue()
        ->and($parser->hasCollectableRewardIndicator(dailyQuestRewardsTask09Fixture('indicator-element.md')))->toBeFalse()
        ->and($parser->hasCollectableRewardIndicator(file_get_contents(base_path('tests/Fixtures/travian-samples/dorf1.php.html'))))->toBeFalse();
});

test('parser extracts the daily quest last seen timestamp', function () {
    $lastSeenAt = app(DailyQuestRewardsParser::class)->parseLastSeenAt(
        dailyQuestRewardsTask09Fixture('step01-graphql.md'),
    );

    expect($lastSeenAt)->toBe(1780611877);
});

test('parser extracts only unlocked and unredeemed daily quest rewards', function () {
    $rewards = app(DailyQuestRewardsParser::class)->parseCollectableRewards(
        dailyQuestRewardsTask09Fixture('step01-graphql2.md'),
    );

    expect($rewards)->toHaveCount(1)
        ->and($rewards[0]->rewardId)->toBe('DailyQuestsReward_01')
        ->and($rewards[0]->requiredPoints)->toBe(25)
        ->and($rewards[0]->achievedPoints)->toBe(27)
        ->and($rewards[0]->collectionPayload())->toBe([
            'action' => 'dailyQuest',
            'questId' => 'DailyQuestsReward_01',
        ]);
});

test('parser ignores refresh payloads that do not contain reward ids', function () {
    $rewards = app(DailyQuestRewardsParser::class)->parseCollectableRewards(
        dailyQuestRewardsTask09Fixture('step02-graphql.md'),
    );

    expect($rewards)->toBeEmpty();
});
