<?php

use App\Application\Accounts\Rewards\ExecuteQuestRewardCollection;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeQuestRewardAccount(bool $acceptQuests = true): array
{
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
    ]);

    $account->settings()->create([
        'resource_priorities' => [15, 11, 1, 1],
        'accept_quests' => $acceptQuests,
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية جديدة',
        'x' => 9,
        'y' => 60,
        'population' => 64,
        'is_active' => true,
    ]);

    return [$account->fresh('settings'), $village];
}

function questRewardTasksPayload(array $tasks): string
{
    return json_encode([
        'tasksData' => [
            'generalTasks' => [],
            'activeVillageTasks' => $tasks,
            'rewardBonus' => [
                'heroLevel' => 8,
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

function questRewardTask(string $type, ?int $buildingId = null): array
{
    $metadata = $buildingId !== null ? ['buildingId' => $buildingId] : null;

    return [
        'name' => $type,
        'type' => $type,
        'scope' => 'settledVillage',
        'metadata' => $metadata,
        'levels' => [
            [
                'title' => $type.' reward',
                'metadata' => $metadata,
                'wasCollected' => false,
                'readyToBeCollected' => true,
                'levelValue' => 1,
                'level' => 1,
                'rewardValues' => [
                    'resources' => 85,
                    'experience' => 5,
                ],
                'questId' => $type.'_1',
            ],
        ],
    ];
}

function questRewardSession(array $getResponses = [], array $postJsonResponses = []): AccountSession
{
    return new class($getResponses, $postJsonResponses) implements AccountSession
    {
        /**
         * @var list<array{uri: string, options: array<string, mixed>}>
         */
        public array $getRequests = [];

        /**
         * @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}>
         */
        public array $jsonRequests = [];

        /**
         * @param  array<string, string|list<string>>  $getResponses
         * @param  array<string, string|list<string>>  $postJsonResponses
         */
        public function __construct(
            protected array $getResponses,
            protected array $postJsonResponses,
        ) {}

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->getRequests[] = [
                'uri' => $uri,
                'options' => $options,
            ];

            return new SessionResponse(200, $this->resolve($this->getResponses, $uri), $this->effectiveUri($uri), []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '[]', $this->effectiveUri($uri), []);
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->jsonRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(200, $this->resolve($this->postJsonResponses, $uri), $this->effectiveUri($uri), []);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->postJson($uri, $payload, $options);
        }

        public function persist(): void {}

        /**
         * @param  array<string, string|list<string>>  $responses
         */
        protected function resolve(array &$responses, string $uri): string
        {
            $path = parse_url($uri, PHP_URL_PATH) ?: $uri;
            $keys = [$uri, $path];

            if ($path === '/tasks') {
                $keys[] = '/tasks?t=village';
            }

            foreach ($keys as $key) {
                if (array_key_exists($key, $responses)) {
                    return $this->nextResponse($responses, $key);
                }
            }

            throw new RuntimeException("No fake response registered for [{$uri}].");
        }

        /**
         * @param  array<string, string|list<string>>  $responses
         */
        protected function nextResponse(array &$responses, string $key): string
        {
            $response = $responses[$key];

            if (is_array($response)) {
                $nextResponse = array_shift($response);
                $responses[$key] = $response;

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

test('quest reward collection follows browser request order and reloads tasks between rewards', function () {
    [$account, $village] = makeQuestRewardAccount();
    $session = questRewardSession([
        '/tasks?t=village' => questRewardTasksPayload([
            questRewardTask('populationProgressInVillage'),
        ]),
        '/api/v1/hero/dataForHUD' => '{}',
        '/api/v1/progressive-tasks/reload' => [
            questRewardTasksPayload([
                questRewardTask('buildingProgress', 17),
            ]),
            questRewardTasksPayload([]),
        ],
    ], [
        '/api/v1/progressive-tasks/collectReward' => '{"success":true}',
    ]);

    app(ExecuteQuestRewardCollection::class)->handle($account, $village, $session, new SessionResponse(
        statusCode: 200,
        body: '<div class="bigSpeechBubble newQuestSpeechBubble" title=""></div>',
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php?newdid=23378',
        headers: [],
    ));

    expect($session->getRequests)->toHaveCount(5)
        ->and($session->getRequests[0]['uri'])->toBe('/tasks?t=village')
        ->and($session->jsonRequests)->toHaveCount(2)
        ->and($session->jsonRequests[0]['payload'])->toBe([
            'questType' => 'populationProgressInVillage',
            'scope' => 'settledVillage',
            'targetLevel' => 1,
            'heroLevel' => 8,
        ])
        ->and($session->getRequests[1]['uri'])->toBe('/api/v1/hero/dataForHUD')
        ->and($session->getRequests[2]['uri'])->toBe('/api/v1/progressive-tasks/reload')
        ->and($session->jsonRequests[1]['payload'])->toBe([
            'questType' => 'buildingProgress',
            'scope' => 'settledVillage',
            'targetLevel' => 1,
            'heroLevel' => 8,
            'buildingId' => 17,
        ])
        ->and($session->getRequests[3]['uri'])->toBe('/api/v1/hero/dataForHUD')
        ->and($session->getRequests[4]['uri'])->toBe('/api/v1/progressive-tasks/reload');

    expect(ActivityLog::query()->where('activity_type', ActivityType::Quest)->where('status', ActivityLogStatus::Done)->count())->toBe(2);
});

test('quest reward collection can react to an already observed dorf1 response', function () {
    [$account, $village] = makeQuestRewardAccount();
    $session = questRewardSession([
        '/tasks?t=village' => questRewardTasksPayload([
            questRewardTask('populationProgressInVillage'),
        ]),
        '/api/v1/hero/dataForHUD' => '{}',
        '/api/v1/progressive-tasks/reload' => questRewardTasksPayload([]),
    ], [
        '/api/v1/progressive-tasks/collectReward' => '{"success":true}',
    ]);

    app(ExecuteQuestRewardCollection::class)->handleObservedDorf1Response($account, $session, new SessionResponse(
        statusCode: 200,
        body: '<input id="villageName" data-did="'.$village->travian_village_id.'"><div class="bigSpeechBubble newQuestSpeechBubble" title=""></div>',
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php?newdid='.$village->travian_village_id,
        headers: [],
    ));

    expect($session->getRequests[0]['uri'])->toBe('/tasks?t=village')
        ->and($session->jsonRequests)->toHaveCount(1)
        ->and(ActivityLog::query()->where('activity_type', ActivityType::Quest)->where('status', ActivityLogStatus::Done)->count())->toBe(1);
});

test('quest reward collection stays idle when account setting is disabled', function () {
    [$account, $village] = makeQuestRewardAccount(false);
    $session = questRewardSession();

    app(ExecuteQuestRewardCollection::class)->handle($account, $village, $session, new SessionResponse(
        statusCode: 200,
        body: '<div class="bigSpeechBubble newQuestSpeechBubble" title=""></div>',
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php?newdid=23378',
        headers: [],
    ));

    expect($session->getRequests)->toBeEmpty()
        ->and($session->jsonRequests)->toBeEmpty()
        ->and(ActivityLog::query()->exists())->toBeFalse();
});

test('quest reward collection stays idle without a dorf1 source response', function () {
    [$account, $village] = makeQuestRewardAccount();
    $session = questRewardSession();

    app(ExecuteQuestRewardCollection::class)->handle($account, $village, $session);

    expect($session->getRequests)->toBeEmpty()
        ->and($session->jsonRequests)->toBeEmpty()
        ->and(ActivityLog::query()->exists())->toBeFalse();
});

test('quest reward collection does not open tasks when dorf1 has no reward indicator', function () {
    [$account, $village] = makeQuestRewardAccount();
    $session = questRewardSession();

    app(ExecuteQuestRewardCollection::class)->handle($account, $village, $session, new SessionResponse(
        statusCode: 200,
        body: '<html><body>dorf1 without task bubble</body></html>',
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php?newdid=23378',
        headers: [],
    ));

    expect($session->getRequests)->toBeEmpty()
        ->and($session->jsonRequests)->toBeEmpty()
        ->and(ActivityLog::query()->exists())->toBeFalse();
});

test('quest reward collection logs when an indicator opens an empty tasks payload', function () {
    [$account, $village] = makeQuestRewardAccount();
    $session = questRewardSession([
        '/tasks?t=village' => questRewardTasksPayload([]),
    ]);

    app(ExecuteQuestRewardCollection::class)->handle($account, $village, $session, new SessionResponse(
        statusCode: 200,
        body: '<div class="bigSpeechBubble newQuestSpeechBubble" title=""></div>',
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php?newdid=23378',
        headers: [],
    ));

    $log = ActivityLog::query()->first();

    expect($session->getRequests)->toHaveCount(1)
        ->and($session->jsonRequests)->toBeEmpty()
        ->and($log?->activity_type)->toBe(ActivityType::Quest)
        ->and($log?->status)->toBe(ActivityLogStatus::Pending);
});
