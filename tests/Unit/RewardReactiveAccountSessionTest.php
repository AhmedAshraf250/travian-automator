<?php

use App\Application\Accounts\Rewards\ObservedDailyQuestRewardReaction;
use App\Application\Accounts\Rewards\ObservedQuestRewardReaction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Session\RewardReactiveAccountSession;
use App\Models\Account;

test('reward session ignores successful dorf1 responses from another host', function () {
    $account = Account::factory()->make([
        'server_url' => 'https://travian.example/',
    ]);
    $inner = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '<div class="newQuestSpeechBubble"></div>', 'https://outside.example/dorf1.php', []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('Not expected.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('Not expected.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('Not expected.');
        }

        public function persist(): void {}
    };
    $quests = Mockery::mock(ObservedQuestRewardReaction::class);
    $daily = Mockery::mock(ObservedDailyQuestRewardReaction::class);
    $quests->shouldNotReceive('handle');
    $daily->shouldNotReceive('handle');

    $response = (new RewardReactiveAccountSession($account, $inner, $quests, $daily))->get('/dorf1.php');

    expect($response->effectiveUri)->toBe('https://outside.example/dorf1.php');
});
