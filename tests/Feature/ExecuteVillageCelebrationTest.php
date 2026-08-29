<?php

use App\Application\Accounts\Celebrations\ExecuteVillageCelebration;
use App\Application\Accounts\Hero\UseHeroResourcesForCost;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

function fakeTownHallCelebrationHtml(
    int $smallPoints = 181,
    ?string $smallActionUri = '/build.php?id=37&gid=24&action=celebration&do=1&t=1',
    ?int $greatPoints = null,
    ?string $greatActionUri = null,
    bool $running = false,
    bool $withCost = false,
): string {
    $smallActionMarkup = $smallActionUri !== null
        ? '<a class="textButtonV1 green" href="'.$smallActionUri.'">Organize</a>'
        : '';

    $greatMarkup = '';

    if ($greatPoints !== null) {
        $greatActionMarkup = $greatActionUri !== null
            ? '<a class="textButtonV1 green" href="'.$greatActionUri.'">Organize</a>'
            : '';

        $greatMarkup = <<<HTML
        <div class="research">
            <div class="information">
                <div class="title">
                    <a>احتفال كبير</a>
                    <span class="points">{$greatPoints} نقاط حضارية</span>
                </div>
                <div class="cta">{$greatActionMarkup}</div>
            </div>
        </div>
        HTML;
    }

    $runningMarkup = $running
        ? <<<'HTML'
        <h4 class="round">إحتفالات قائمة</h4>
        <table class="under_progress"><tbody><tr><td class="desc">احتفال صغير</td></tr></tbody></table>
        HTML
        : '';
    $costMarkup = $withCost
        ? '<div class="resourceWrapper charges"><div class="resource"><i class="r1Big"></i><span class="value">640</span></div><div class="resource"><i class="r2Big"></i><span class="value">665</span></div><div class="resource"><i class="r3Big"></i><span class="value">594</span></div><div class="resource"><i class="r4Big"></i><span class="value">1340</span></div></div><div class="errorMessage">Resources are insufficient.</div>'
        : '';

    return <<<HTML
    <div class="build_details researches">
        <div class="research">
            <div class="information">
                <div class="title">
                    <a>احتفال صغير</a>
                    <span class="points">{$smallPoints} نقاط حضارية</span>
                </div>
                {$costMarkup}<div class="cta">{$smallActionMarkup}</div>
            </div>
        </div>
        {$greatMarkup}
        {$runningMarkup}
    </div>
    HTML;
}

test('village celebration automation starts a small celebration when the threshold is met', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية احتفال',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'celebration_enabled' => true,
        'celebration_type' => 'small',
        'celebration_min_culture_points' => 180,
    ]);

    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 1,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        /** @var array<string, array<string, mixed>> */
        public array $requestOptions = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;
            $this->requestOptions[$uri] = $options;

            return match ($uri) {
                '/build.php?id=37&gid=24' => new SessionResponse(200, fakeTownHallCelebrationHtml(), 'https://example.com/build.php?id=37&gid=24', []),
                '/build.php?id=37&gid=24&action=celebration&do=1&t=1' => new SessionResponse(200, '', 'https://example.com/build.php?id=37&t=1', []),
                default => new SessionResponse(200, '', 'https://example.com'.$uri, []),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during celebration execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during celebration execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during celebration execution.');
        }

        public function persist(): void {}
    };

    app(ExecuteVillageCelebration::class)->handle(
        $account->fresh(),
        $village->fresh(['settings', 'buildings']),
        $session,
    );

    $log = ActivityLog::query()->where('activity_type', ActivityType::Celebration)->latest('id')->first();

    expect($session->requests)->toContain('/build.php?id=37&gid=24');
    expect($session->requests)->toContain('/build.php?id=37&gid=24&action=celebration&do=1&t=1');
    expect($session->requestOptions['/build.php?id=37&gid=24']['headers']['Referer'] ?? null)->toBe('https://example.com/dorf2.php');
    expect($session->requestOptions['/build.php?id=37&gid=24&action=celebration&do=1&t=1']['headers']['Referer'] ?? null)->toBe('https://example.com/build.php?id=37&gid=24');
    expect($log?->payload['type'] ?? null)->toBe('small');
    expect($log?->payload['culture_points'] ?? null)->toBe(181);
});

test('celebration setting can use hero resources for a verified shortage and retry the Town Hall page', function () {
    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create(['travian_village_id' => '23381', 'name' => 'Hero celebration', 'is_active' => true]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'celebration_enabled' => true,
        'celebration_type' => 'small',
        'celebration_min_culture_points' => 180,
        'celebration_use_hero_resources' => true,
    ]);
    $village->buildings()->create(['slot_id' => 37, 'building_gid' => 24, 'building_type' => 'Town Hall', 'current_level' => 1]);

    $session = new class implements AccountSession
    {
        public int $townHallReads = 0;

        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            if ($uri === '/build.php?id=37&gid=24') {
                $this->townHallReads++;
                $action = $this->townHallReads > 1 ? '/build.php?id=37&gid=24&action=celebration&do=1&t=1' : null;

                return new SessionResponse(200, fakeTownHallCelebrationHtml(smallActionUri: $action, withCost: true), 'https://example.com/build.php?id=37&gid=24', []);
            }

            return new SessionResponse(200, '', 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected.');
        }

        public function persist(): void {}
    };

    mock(UseHeroResourcesForCost::class)
        ->shouldReceive('handleCost')
        ->once()
        ->withArgs(fn (...$arguments): bool => ($arguments[3]['wood'] ?? null) === 640)
        ->andReturnTrue();

    app(ExecuteVillageCelebration::class)->handle($account, $village->fresh(['settings', 'buildings']), $session);

    expect($session->townHallReads)->toBe(2)
        ->and($session->requests)->toContain('/build.php?id=37&gid=24&action=celebration&do=1&t=1');
});

test('village celebration automation skips celebrations below the configured threshold', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'قرية حد أدنى',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'celebration_enabled' => true,
        'celebration_type' => 'small',
        'celebration_min_culture_points' => 250,
    ]);

    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 1,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return new SessionResponse(200, fakeTownHallCelebrationHtml(smallPoints: 181), 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during celebration execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during celebration execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during celebration execution.');
        }

        public function persist(): void {}
    };

    app(ExecuteVillageCelebration::class)->handle(
        $account->fresh(),
        $village->fresh(['settings', 'buildings']),
        $session,
    );

    expect($session->requests)->toBe([
        '/dorf1.php?newdid=23379',
        '/build.php?id=37&gid=24',
    ]);
    expect(ActivityLog::query()->where('activity_type', ActivityType::Celebration)->exists())->toBeFalse();
});

test('village celebration automation prefers a great celebration in great mode when it is available', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23380',
        'name' => 'قرية احتفال كبير',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'celebration_enabled' => true,
        'celebration_type' => 'great',
        'celebration_min_culture_points' => 300,
    ]);

    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 10,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/build.php?id=37&gid=24' => new SessionResponse(
                    200,
                    fakeTownHallCelebrationHtml(
                        smallPoints: 500,
                        greatPoints: 2000,
                        greatActionUri: '/build.php?id=37&gid=24&action=celebration&do=2&t=1',
                    ),
                    'https://example.com/build.php?id=37&gid=24',
                    [],
                ),
                '/build.php?id=37&gid=24&action=celebration&do=2&t=1' => new SessionResponse(200, '', 'https://example.com/build.php?id=37&t=1', []),
                default => new SessionResponse(200, '', 'https://example.com'.$uri, []),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during celebration execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during celebration execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during celebration execution.');
        }

        public function persist(): void {}
    };

    app(ExecuteVillageCelebration::class)->handle(
        $account->fresh(),
        $village->fresh(['settings', 'buildings']),
        $session,
    );

    $log = ActivityLog::query()->where('activity_type', ActivityType::Celebration)->latest('id')->first();

    expect($session->requests)->toContain('/build.php?id=37&gid=24&action=celebration&do=2&t=1');
    expect($log?->payload['type'] ?? null)->toBe('great');
    expect($log?->payload['culture_points'] ?? null)->toBe(2000);
});
