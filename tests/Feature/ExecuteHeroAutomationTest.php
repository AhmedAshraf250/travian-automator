<?php

use App\Application\Accounts\Hero\ExecuteHeroAutomation;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function heroAutomationTask05Fixture(string $path): string
{
    return file_get_contents(base_path('tests/Fixtures/travian-samples/task05/'.$path));
}

function heroAutomationCopiedResponseFixture(string $path): string
{
    $contents = heroAutomationTask05Fixture($path);
    $marker = '# copy response:';
    $position = strpos($contents, $marker);

    if ($position === false) {
        return $contents;
    }

    return trim(substr($contents, $position + strlen($marker)));
}

function makeHeroAutomationAccount(array $settings = [], array $state = []): Account
{
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
        'user_agent' => null,
    ]);

    $account->settings()->create([
        'resource_priorities' => AccountSetting::defaultResourcePriorities(),
        'hero_use_global_settings' => false,
        'hero_adventures_enabled' => false,
        'hero_min_health' => 40,
        'hero_revive_enabled' => false,
        'hero_attribute_upgrade_enabled' => false,
        'hero_attribute_weights' => [
            'power' => 0,
            'offBonus' => 0,
            'defBonus' => 0,
            'productionPoints' => 0,
        ],
        ...$settings,
    ]);

    if ($state !== []) {
        $account->heroState()->create([
            'status' => 'home',
            'health_percent' => 95,
            'experience_percent' => 48,
            'adventures_available_count' => 0,
            'has_unspent_attribute_points' => false,
            'seen_at' => now(),
            ...$state,
        ]);
    }

    return $account->fresh(['settings', 'heroState']);
}

function heroAutomationSession(array $getResponses = [], array $postJsonResponses = []): AccountSession
{
    return new class($getResponses, $postJsonResponses) implements AccountSession
    {
        /**
         * @var list<array{uri: string, options: array<string, mixed>}>
         */
        public array $getRequests = [];

        /**
         * @var list<array{method: string, uri: string, payload: array<string, mixed>, options: array<string, mixed>}>
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
                'method' => 'POST',
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(200, $this->resolve($this->postJsonResponses, $uri), $this->effectiveUri($uri), []);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->jsonRequests[] = [
                'method' => 'PUT',
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(
                200,
                $this->resolve($this->postJsonResponses, $uri),
                $this->effectiveUri($uri),
                $uri === '/api/v1/troop/send' ? ['x-nonce' => ['test-confirmation-nonce']] : [],
            );
        }

        public function persist(): void {}

        /**
         * @param  array<string, string>  $responses
         */
        protected function resolve(array &$responses, string $uri): string
        {
            if (array_key_exists($uri, $responses)) {
                return $this->nextResponse($responses, $uri);
            }

            $path = parse_url($uri, PHP_URL_PATH) ?: $uri;

            if (array_key_exists($path, $responses)) {
                return $this->nextResponse($responses, $path);
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

test('hero automation does not send adventure below health threshold', function () {
    $account = makeHeroAutomationAccount([
        'hero_adventures_enabled' => true,
        'hero_min_health' => 40,
    ], [
        'status' => 'home',
        'health_percent' => 20,
        'adventures_available_count' => 2,
    ]);
    $session = heroAutomationSession();

    app(ExecuteHeroAutomation::class)->handle($account, $session);

    expect($session->getRequests)->toBeEmpty()
        ->and($session->jsonRequests)->toBeEmpty();

    $log = ActivityLog::query()->first();

    expect($log?->activity_type)->toBe(ActivityType::Hero)
        ->and($log?->status)->toBe(ActivityLogStatus::Pending)
        ->and($log?->payload['blocked_reason'] ?? null)->toBeNull()
        ->and($log?->payload['min_health'])->toBe(40);
});

test('hero automation sends the first adventure with preview and nonce confirmation', function () {
    $account = makeHeroAutomationAccount([
        'hero_adventures_enabled' => true,
        'hero_min_health' => 40,
    ], [
        'status' => 'home',
        'health_percent' => 95,
        'adventures_available_count' => 2,
    ]);
    $session = heroAutomationSession([
        '/hero/adventures' => heroAutomationTask05Fixture('step01-press-adventure-button-response.md'),
        '/api/v1/hero/dataForHUD' => heroAutomationCopiedResponseFixture('dataForHUD.md'),
    ], [
        '/api/v1/troop/send' => heroAutomationTask05Fixture('step02-send-hero-toAdventure-response1.md'),
    ]);

    app(ExecuteHeroAutomation::class)->handle($account, $session);

    expect($session->jsonRequests)->toHaveCount(2)
        ->and($session->jsonRequests[0]['method'])->toBe('PUT')
        ->and($session->jsonRequests[0]['uri'])->toBe('/api/v1/troop/send')
        ->and($session->jsonRequests[0]['payload']['target']['adventureId'])->toBe(55)
        ->and($session->jsonRequests[0]['payload']['troops'])->toBe([['t11' => 1]])
        ->and($session->jsonRequests[1]['method'])->toBe('POST')
        ->and($session->jsonRequests[1]['uri'])->toBe('/api/v1/troop/send')
        ->and($session->jsonRequests[1]['payload']['target']['adventureId'])->toBe(55)
        ->and($session->jsonRequests[1]['payload']['troops'])->toBe([['t11' => 1]])
        ->and($session->jsonRequests[1]['options']['headers']['X-Nonce'] ?? null)->toBe('test-confirmation-nonce');

    $account->refresh();

    expect($account->heroState?->status)->toBe('adventure')
        ->and($account->heroState?->hero_remaining_seconds)->toBe(2622);
});

test('hero automation refreshes an elapsed returning timer before deciding adventure availability', function () {
    $account = makeHeroAutomationAccount([
        'hero_adventures_enabled' => true,
        'hero_min_health' => 40,
    ], [
        'status' => 'returning',
        'health_percent' => 95,
        'adventures_available_count' => 2,
        'hero_remaining_seconds' => 10,
        'seen_at' => now()->subMinute(),
    ]);
    $session = heroAutomationSession([
        '/api/v1/hero/dataForHUD' => json_encode([
            'healthStatus' => 'alive',
            'health' => 95,
            'experiencePercent' => 48,
            'level' => 1,
            'levelUp' => false,
            'statusInlineIcon' => '<i class="heroHome"></i>',
            'heroStatusTitle' => '',
        ], JSON_THROW_ON_ERROR),
        '/hero/adventures' => heroAutomationTask05Fixture('step01-press-adventure-button-response.md'),
    ], [
        '/api/v1/troop/send' => heroAutomationTask05Fixture('step02-send-hero-toAdventure-response1.md'),
    ]);

    app(ExecuteHeroAutomation::class)->handle($account, $session);

    expect($session->getRequests[0]['uri'])->toBe('/api/v1/hero/dataForHUD')
        ->and($session->jsonRequests)->toHaveCount(2)
        ->and($session->jsonRequests[0]['method'])->toBe('PUT')
        ->and($session->jsonRequests[1]['method'])->toBe('POST');
});

test('hero automation revives a dead hero when resources are available', function () {
    $account = makeHeroAutomationAccount([
        'hero_revive_enabled' => true,
    ], [
        'status' => 'dead',
        'health_percent' => 0,
    ]);
    $session = heroAutomationSession([
        '/hero' => '<html></html>',
        '/api/v1/hero/v2/screen/attributes' => [
            heroAutomationTask05Fixture('dead-hero/step02-choose-attributes-response.md'),
            heroAutomationCopiedResponseFixture('dead-hero/step03-attributes.md'),
        ],
        '/api/v1/hero/dataForHUD' => heroAutomationCopiedResponseFixture('dead-hero/step03-dataForHUD.md'),
    ], [
        '/api/v1/hero/v2/revive' => '[]',
    ]);

    app(ExecuteHeroAutomation::class)->handle($account, $session);

    expect($session->jsonRequests)->toHaveCount(1)
        ->and($session->jsonRequests[0]['method'])->toBe('POST')
        ->and($session->jsonRequests[0]['uri'])->toBe('/api/v1/hero/v2/revive');

    expect($account->fresh('heroState')->heroState?->status)->toBe('regenerating')
        ->and($account->fresh('heroState')->heroState?->hero_remaining_seconds)->toBe(14400);
    expect(ActivityLog::query()->where('activity_type', ActivityType::Hero)->where('status', ActivityLogStatus::Done)->exists())->toBeTrue();
});

test('hero automation waits when revive resources are not available', function () {
    $payload = json_decode(heroAutomationTask05Fixture('dead-hero/step02-choose-attributes-response.md'), true, flags: JSON_THROW_ON_ERROR);
    $payload['revive']['chargesErrorMessage'] = 'لا توجد موارد كافية.';

    $account = makeHeroAutomationAccount([
        'hero_revive_enabled' => true,
    ], [
        'status' => 'dead',
        'health_percent' => 0,
    ]);
    $session = heroAutomationSession([
        '/hero' => '<html></html>',
        '/api/v1/hero/v2/screen/attributes' => json_encode($payload, JSON_THROW_ON_ERROR),
    ]);

    app(ExecuteHeroAutomation::class)->handle($account, $session);

    expect($session->jsonRequests)->toBeEmpty();

    $log = ActivityLog::query()->first();

    expect($log?->activity_type)->toBe(ActivityType::Hero)
        ->and($log?->status)->toBe(ActivityLogStatus::Pending)
        ->and($log?->payload['blocked_reason'])->toBe('resources_or_crop');
});

test('hero automation distributes free attribute points by configured weights', function () {
    $attributesPayload = json_decode(heroAutomationCopiedResponseFixture('Experience-Points-Distribution/3-attributes.md'), true, flags: JSON_THROW_ON_ERROR);
    $attributesPayload['hero']['freePoints'] = 4;

    $account = makeHeroAutomationAccount([
        'hero_attribute_upgrade_enabled' => true,
        'hero_attribute_weights' => [
            'power' => 1,
            'offBonus' => 1,
            'defBonus' => 1,
            'productionPoints' => 1,
        ],
    ], [
        'status' => 'home',
        'health_percent' => 95,
        'has_unspent_attribute_points' => true,
        'unspent_attribute_points' => 4,
    ]);
    $session = heroAutomationSession([
        '/hero' => '<html></html>',
        '/api/v1/hero/v2/screen/attributes' => json_encode($attributesPayload, JSON_THROW_ON_ERROR),
        '/api/v1/hero/dataForHUD' => heroAutomationCopiedResponseFixture('Experience-Points-Distribution/2-dataForHUD.md'),
    ], [
        '/api/v1/hero/v2/attributes' => '[]',
    ]);

    app(ExecuteHeroAutomation::class)->handle($account, $session);

    expect($session->jsonRequests)->toHaveCount(1)
        ->and($session->jsonRequests[0]['method'])->toBe('POST')
        ->and($session->jsonRequests[0]['uri'])->toBe('/api/v1/hero/v2/attributes')
        ->and($session->jsonRequests[0]['payload']['attributes'])->toBe([
            'power' => 1,
            'offBonus' => 1,
            'defBonus' => 1,
            'productionPoints' => 1,
        ]);
});
