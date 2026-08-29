<?php

use App\Application\Accounts\Rewards\ObservedDailyQuestRewardReaction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeDailyQuestRewardAccount(bool $acceptQuests = true): Account
{
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
    ]);

    $account->settings()->create([
        'accept_quests' => $acceptQuests,
    ]);

    return $account->fresh('settings');
}

function dailyQuestRewardDorf1Body(): string
{
    return '<a class="dailyQuests" href="#" accesskey="7" onclick="Travian.React.openDailyQuestsDialog(); return false;"><div class="indicator">!</div></a>';
}

function dailyQuestRewardLastSeenPayload(int $lastSeenAt = 1780611877): string
{
    return json_encode([
        'data' => [
            'ownPlayer' => [
                'dailyQuests' => [
                    'lastSeenAt' => $lastSeenAt,
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

function dailyQuestRewardDetailsPayload(int $achievedPoints = 27, ?array $rewards = null): string
{
    return json_encode([
        'data' => [
            'ownPlayer' => [
                'dailyQuests' => [
                    'achievedPoints' => $achievedPoints,
                    'day' => 36,
                    'quests' => [],
                    'resetAt' => 1780660800,
                    'rewards' => $rewards ?? [
                        dailyQuestRewardPayload('DailyQuestsReward_01', 25),
                        dailyQuestRewardPayload('DailyQuestsReward_02', 50),
                    ],
                ],
                'prevState' => [
                    'achievedPoints' => $achievedPoints,
                    'quests' => [],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

function dailyQuestRewardPayload(string $id, int $points, bool $awardRedeemed = false): array
{
    return [
        'id' => $id,
        'awardDescription' => 'achievementQuests.'.$id,
        'awardRedeemed' => $awardRedeemed,
        'points' => $points,
        'possibleAwards' => [
            [
                'resetAt' => 1780660800,
                'description' => 'achievementQuests.'.$id,
                'reward' => [
                    [
                        'type' => 'allResources',
                        'value' => 200,
                    ],
                ],
            ],
        ],
    ];
}

function dailyQuestRewardRefreshPayload(): string
{
    return json_encode([
        'data' => [
            'ownPlayer' => [
                'dailyQuests' => [
                    'achievedPoints' => 27,
                    'rewards' => [
                        [
                            'points' => 25,
                            'awardRedeemed' => true,
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

function dailyQuestRewardSession(array $postJsonResponses = []): AccountSession
{
    return new class($postJsonResponses) implements AccountSession
    {
        /**
         * @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}>
         */
        public array $jsonRequests = [];

        /**
         * @param  array<string, string|list<string>>  $postJsonResponses
         */
        public function __construct(
            protected array $postJsonResponses,
        ) {}

        public function get(string $uri, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '', $this->effectiveUri($uri), []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '', $this->effectiveUri($uri), []);
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->jsonRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(200, $this->resolve($uri), $this->effectiveUri($uri), []);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->postJson($uri, $payload, $options);
        }

        public function persist(): void {}

        protected function resolve(string $uri): string
        {
            $path = parse_url($uri, PHP_URL_PATH) ?: $uri;

            foreach ([$uri, $path] as $key) {
                if (array_key_exists($key, $this->postJsonResponses)) {
                    return $this->nextResponse($key);
                }
            }

            throw new RuntimeException("No fake response registered for [{$uri}].");
        }

        protected function nextResponse(string $key): string
        {
            $response = $this->postJsonResponses[$key];

            if (is_array($response)) {
                $nextResponse = array_shift($response);
                $this->postJsonResponses[$key] = $response;

                return (string) $nextResponse;
            }

            return $response;
        }

        protected function effectiveUri(string $uri): string
        {
            if (preg_match('/^https?:\/\//i', $uri) === 1) {
                return $uri;
            }

            return 'https://ts7.x1.arabics.travian.com/'.ltrim($uri, '/');
        }
    };
}

test('daily quest reward collection follows browser request order and collects one account reward', function () {
    $account = makeDailyQuestRewardAccount();
    $session = dailyQuestRewardSession([
        '/api/v1/graphql' => [
            dailyQuestRewardLastSeenPayload(),
            dailyQuestRewardDetailsPayload(),
            dailyQuestRewardRefreshPayload(),
        ],
        '/api/v1/daily-quest/award' => '',
    ]);

    app(ObservedDailyQuestRewardReaction::class)->handle($account, $session, new SessionResponse(
        statusCode: 200,
        body: dailyQuestRewardDorf1Body(),
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php',
        headers: [],
    ));

    expect($session->jsonRequests)->toHaveCount(4)
        ->and($session->jsonRequests[0]['uri'])->toBe('/api/v1/graphql')
        ->and($session->jsonRequests[0]['payload'])->toBe([
            'query' => '{ownPlayer{dailyQuests{lastSeenAt}}}',
        ])
        ->and($session->jsonRequests[1]['uri'])->toBe('/api/v1/graphql')
        ->and($session->jsonRequests[1]['payload']['variables'])->toBe([
            'lastSeenAt' => 1780611877,
        ])
        ->and(str_contains($session->jsonRequests[1]['payload']['query'], 'prevState:dailyQuests'))->toBeTrue()
        ->and($session->jsonRequests[2]['uri'])->toBe('/api/v1/daily-quest/award')
        ->and($session->jsonRequests[2]['payload'])->toBe([
            'action' => 'dailyQuest',
            'questId' => 'DailyQuestsReward_01',
        ])
        ->and($session->jsonRequests[3]['uri'])->toBe('/api/v1/graphql')
        ->and($session->jsonRequests[3]['payload'])->toBe([
            'query' => '{ownPlayer{dailyQuests{achievedPoints rewards{points awardRedeemed}}}}',
        ]);

    $log = ActivityLog::query()->first();

    expect($log?->activity_type)->toBe(ActivityType::Quest)
        ->and($log?->status)->toBe(ActivityLogStatus::Done)
        ->and($log?->village_id)->toBeNull()
        ->and($log?->payload['reward']['reward_id'])->toBe('DailyQuestsReward_01');
});

test('daily quest reward collection stays idle when account setting is disabled', function () {
    $account = makeDailyQuestRewardAccount(false);
    $session = dailyQuestRewardSession();

    app(ObservedDailyQuestRewardReaction::class)->handle($account, $session, new SessionResponse(
        statusCode: 200,
        body: dailyQuestRewardDorf1Body(),
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php',
        headers: [],
    ));

    expect($session->jsonRequests)->toBeEmpty()
        ->and(ActivityLog::query()->exists())->toBeFalse();
});

test('daily quest reward collection ignores other page indicators', function () {
    $account = makeDailyQuestRewardAccount();
    $session = dailyQuestRewardSession();

    app(ObservedDailyQuestRewardReaction::class)->handle($account, $session, new SessionResponse(
        statusCode: 200,
        body: '<a class="dailyQuests" href="#"></a><a class="reports" href="/report"><div class="indicator">!</div></a>',
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php',
        headers: [],
    ));

    expect($session->jsonRequests)->toBeEmpty()
        ->and(ActivityLog::query()->exists())->toBeFalse();
});

test('daily quest reward collection logs pending when indicator has no unlocked reward', function () {
    $account = makeDailyQuestRewardAccount();
    $session = dailyQuestRewardSession([
        '/api/v1/graphql' => [
            dailyQuestRewardLastSeenPayload(),
            dailyQuestRewardDetailsPayload(10, [
                dailyQuestRewardPayload('DailyQuestsReward_01', 25),
            ]),
        ],
    ]);

    app(ObservedDailyQuestRewardReaction::class)->handle($account, $session, new SessionResponse(
        statusCode: 200,
        body: dailyQuestRewardDorf1Body(),
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php',
        headers: [],
    ));

    $log = ActivityLog::query()->first();

    expect($session->jsonRequests)->toHaveCount(2)
        ->and($log?->activity_type)->toBe(ActivityType::Quest)
        ->and($log?->status)->toBe(ActivityLogStatus::Pending)
        ->and($log?->village_id)->toBeNull();
});
