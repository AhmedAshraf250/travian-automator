<?php

use App\Application\Accounts\Connection\AccountConnectionBackoffStarted;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedDorf2Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageResourceState;
use App\Application\Accounts\Sync\Data\ParsedVillageRuntimeState;
use App\Application\Accounts\Sync\Data\ParsedVillageSlot;
use App\Application\Accounts\Sync\Data\ParsedVillageSummary;
use App\Application\Accounts\Sync\PersistVillageOverview;
use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('persist village overview defaults only core existing buildings to automation enabled', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'CR7',
        'is_active' => true,
    ]);
    $summary = new ParsedVillageSummary('23378', 'CR7', 9, 60, 82, true);
    $dorf1Overview = new ParsedDorf1Overview(
        activeVillage: $summary,
        resourceState: new ParsedVillageResourceState(100, 100, 100, 100, 10, 10, 10, 10, 0, 800, 800),
        runtimeState: new ParsedVillageRuntimeState(tribeId: 1, troopSlots: [], incomingAttackCount: 0, incomingReinforcementCount: 0, outgoingMovementCount: 0, movementEntries: [], constructionEntries: [], heroStatus: null, heroRemainingSeconds: null),
        villages: [$summary],
        fieldSlots: [
            new ParsedVillageSlot(1, 1, 'Woodcutter', 3, 'field'),
        ],
        constructionQueue: [],
    );
    $dorf2Overview = new ParsedDorf2Overview([
        new ParsedVillageSlot(19, 10, 'Warehouse', 3, 'building'),
        new ParsedVillageSlot(20, 11, 'Granary', 3, 'building'),
        new ParsedVillageSlot(21, 15, 'Main Building', 5, 'building'),
        new ParsedVillageSlot(22, 23, 'Cranny', 2, 'building'),
        new ParsedVillageSlot(23, 24, 'Town Hall', 1, 'building'),
        new ParsedVillageSlot(24, 0, null, 0, 'building', true),
    ]);

    app(PersistVillageOverview::class)->handle($village, $summary, $dorf1Overview, $dorf2Overview);

    expect($village->buildings()->where('slot_id', 1)->value('automation_enabled'))->toBeTrue()
        ->and($village->buildings()->where('slot_id', 19)->value('automation_enabled'))->toBeTrue()
        ->and($village->buildings()->where('slot_id', 20)->value('automation_enabled'))->toBeTrue()
        ->and($village->buildings()->where('slot_id', 21)->value('automation_enabled'))->toBeTrue()
        ->and($village->buildings()->where('slot_id', 22)->value('automation_enabled'))->toBeTrue()
        ->and($village->buildings()->where('slot_id', 23)->value('automation_enabled'))->toBeFalse()
        ->and($village->buildings()->where('slot_id', 24)->value('automation_enabled'))->toBeTrue();

    $village->buildings()->where('slot_id', 23)->update(['automation_enabled' => true]);

    app(PersistVillageOverview::class)->handle($village->fresh(), $summary, $dorf1Overview, $dorf2Overview);

    expect($village->buildings()->where('slot_id', 23)->value('automation_enabled'))->toBeTrue();
});

test('sync account overview stores villages and resource state from dorf1 html', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
        'username' => 'marshal',
        'password' => 'secret',
        'status' => AccountStatus::Paused,
        'last_login_at' => now()->subDay(),
    ]);
    $previousLoginAt = $account->last_login_at;

    $dorf1Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf1.php.html'));
    $dorf2Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf2.php.html'));

    expect($dorf1Html)->not->toBeFalse();
    expect($dorf2Html)->not->toBeFalse();

    app()->bind(AccountSessionFactory::class, function () use ($account, $dorf1Html, $dorf2Html) {
        return new class($account, (string) $dorf1Html, (string) $dorf2Html) implements AccountSessionFactory
        {
            public function __construct(
                protected Account $account,
                protected string $dorf1Html,
                protected string $dorf2Html,
            ) {}

            public function for(Account $account): AccountSession
            {
                return new class($this->account, $this->dorf1Html, $this->dorf2Html) implements AccountSession
                {
                    public function __construct(
                        protected Account $account,
                        protected string $dorf1Html,
                        protected string $dorf2Html,
                    ) {}

                    public function get(string $uri, array $options = []): SessionResponse
                    {
                        $body = str_contains($uri, '/dorf2.php') ? $this->dorf2Html : $this->dorf1Html;
                        $effectiveUri = rtrim($this->account->server_url, '/').(str_contains($uri, '/dorf2.php') ? '/dorf2.php' : '/dorf1.php');

                        return new SessionResponse(
                            statusCode: 200,
                            body: $body,
                            effectiveUri: $effectiveUri,
                            headers: [],
                        );
                    }

                    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
                    {
                        return new SessionResponse(
                            statusCode: 200,
                            body: $this->html,
                            effectiveUri: rtrim($this->account->server_url, '/').'/dorf1.php',
                            headers: [],
                        );
                    }

                    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        return new SessionResponse(
                            statusCode: 200,
                            body: $this->dorf1Html,
                            effectiveUri: rtrim($this->account->server_url, '/').'/dorf1.php',
                            headers: [],
                        );
                    }

                    public function putJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        return $this->postJson($uri, $payload, $options);
                    }

                    public function persist(): void {}
                };
            }
        };
    });

    app(SyncAccountOverview::class)->handle($account->fresh());

    $account->refresh();
    $village = Village::query()->first();

    expect($account->status)->toBe(AccountStatus::Active);
    expect($village)->not->toBeNull();
    expect($village?->travian_village_id)->toBe('23378');
    expect($village?->x)->toBe(9);
    expect($village?->y)->toBe(60);
    expect($village?->population)->toBe(82);
    expect($village?->resourceState?->wood)->toBe(1993);
    expect($village?->resourceState?->warehouse_capacity)->toBe(2300);
    expect($village?->runtimeState)->not->toBeNull();
    expect($village?->runtimeState?->tribe_id)->toBe(1);
    expect($village?->runtimeState?->troop_slots[0] ?? null)->toBe(0);
    expect($village?->runtimeState?->troop_slots[1] ?? null)->toBe(0);
    expect($village?->runtimeState?->incoming_attack_count)->toBe(1);
    expect($village?->runtimeState?->outgoing_movement_count)->toBe(1);
    expect($village?->runtimeState?->construction_entries)->toHaveCount(0);
    expect($village?->buildings()->whereBetween('slot_id', [1, 40])->count())->toBe(40);
    expect($village?->buildings()->where('slot_id', 10)->first()?->building_gid)->toBe(3);
    expect($village?->buildings()->where('slot_id', 26)->first()?->building_gid)->toBe(15);
    expect($village?->buildings()->where('slot_id', 30)->first()?->building_gid)->toBe(0);
    expect($village?->buildings()->where('is_under_construction', true)->count())->toBe(0);
    expect($account->fresh()->last_login_at?->getTimestamp())->toBe($previousLoginAt?->getTimestamp());
    expect(ActivityLog::query()->where('message', 'Account overview synced successfully from dorf1 and dorf2.')->exists())->toBeTrue();
});

test('sync account overview keeps reload auto while collecting switched village snapshots', function () {
    $dorf1Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf1.php.html'));
    $dorf2Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf2.php.html'));

    expect($dorf1Html)->not->toBeFalse();
    expect($dorf2Html)->not->toBeFalse();

    $activeVillage = new ParsedVillageSummary('23378', 'CR7', 9, 60, 82, true);
    $secondVillage = new ParsedVillageSummary('23379', 'AMH7', 10, 61, 100, false, '/dorf1.php?newdid=23379');
    $initialDorf1Overview = new ParsedDorf1Overview(
        activeVillage: $activeVillage,
        resourceState: new ParsedVillageResourceState(100, 100, 100, 100, 10, 10, 10, 10, 0, 800, 800),
        runtimeState: new ParsedVillageRuntimeState(tribeId: 1, troopSlots: [], incomingAttackCount: 0, incomingReinforcementCount: 0, outgoingMovementCount: 0, movementEntries: [], constructionEntries: [], heroStatus: null, heroRemainingSeconds: null),
        villages: [$activeVillage, $secondVillage],
        fieldSlots: [],
        constructionQueue: [],
    );
    $initialDorf2Overview = new ParsedDorf2Overview([]);

    $session = new class((string) $dorf1Html, (string) $dorf2Html) implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function __construct(
            protected string $dorf1Html,
            protected string $dorf2Html,
        ) {}

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return new SessionResponse(
                statusCode: 200,
                body: str_contains($uri, '/dorf2.php') ? $this->dorf2Html : $this->dorf1Html,
                effectiveUri: 'https://example.com'.$uri,
                headers: [],
            );
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            return $this->get($uri, $options);
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->get($uri, $options);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->get($uri, $options);
        }

        public function persist(): void {}
    };

    $method = new ReflectionMethod(SyncAccountOverview::class, 'collectVillageSnapshots');
    $method->setAccessible(true);
    $method->invoke(app(SyncAccountOverview::class), $session, $initialDorf1Overview, $initialDorf2Overview, true);

    expect($session->requests)->toContain('/dorf1.php?newdid=23379');
    expect($session->requests)->toContain('/dorf1.php?reload=auto');
});

test('sync account overview can authenticate through the modern login api flow', function () {
    config()->set('travian.paths.landing', '/');

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
        'username' => 'marshal',
        'password' => 'secret',
        'status' => AccountStatus::Paused,
    ]);

    $loginHtml = file_get_contents(base_path('tests/Fixtures/travian-samples/login.html'));
    $dorf1Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf1.php.html'));
    $dorf2Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf2.php.html'));

    expect($loginHtml)->not->toBeFalse();
    expect($dorf1Html)->not->toBeFalse();
    expect($dorf2Html)->not->toBeFalse();

    app()->bind(AccountSessionFactory::class, function () use ($account, $loginHtml, $dorf1Html, $dorf2Html) {
        return new class($account, (string) $loginHtml, (string) $dorf1Html, (string) $dorf2Html) implements AccountSessionFactory
        {
            public function __construct(
                protected Account $account,
                protected string $loginHtml,
                protected string $dorf1Html,
                protected string $dorf2Html,
            ) {}

            public function for(Account $account): AccountSession
            {
                return new class($this->account, $this->loginHtml, $this->dorf1Html, $this->dorf2Html) implements AccountSession
                {
                    /**
                     * @var list<array{method:string,uri:string}>
                     */
                    public array $requests = [];

                    public function __construct(
                        protected Account $account,
                        protected string $loginHtml,
                        protected string $dorf1Html,
                        protected string $dorf2Html,
                    ) {}

                    public function get(string $uri, array $options = []): SessionResponse
                    {
                        $this->requests[] = ['method' => 'GET', 'uri' => $uri];

                        if ($uri === '/') {
                            return new SessionResponse(
                                statusCode: 200,
                                body: $this->loginHtml,
                                effectiveUri: rtrim($this->account->server_url, '/').'/',
                                headers: [],
                            );
                        }

                        if (str_starts_with($uri, '/api/v1/auth?code=')) {
                            return new SessionResponse(
                                statusCode: 200,
                                body: $this->dorf1Html,
                                effectiveUri: rtrim($this->account->server_url, '/').'/dorf1.php',
                                headers: [],
                            );
                        }

                        if (str_contains($uri, '/dorf2.php')) {
                            return new SessionResponse(
                                statusCode: 200,
                                body: $this->dorf2Html,
                                effectiveUri: rtrim($this->account->server_url, '/').'/dorf2.php',
                                headers: [],
                            );
                        }

                        return new SessionResponse(
                            statusCode: 200,
                            body: $this->dorf1Html,
                            effectiveUri: rtrim($this->account->server_url, '/').'/dorf1.php',
                            headers: [],
                        );
                    }

                    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
                    {
                        throw new RuntimeException('The legacy form login flow should not be used for the modern login api test.');
                    }

                    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        $this->requests[] = ['method' => 'POST', 'uri' => $uri];

                        expect($uri)->toBe('/api/v1/auth/login');
                        expect($payload)->toMatchArray([
                            'name' => 'marshal',
                            'password' => 'secret',
                            'mobileOptimizations' => false,
                        ]);
                        expect($payload['w'])->toBe((string) config('travian.client.window_size'));
                        expect($options['headers']['x-requested-with'] ?? null)->toBe('XMLHttpRequest');
                        expect($options['headers']['x-version'] ?? null)->toBe('417.8');

                        return new SessionResponse(
                            statusCode: 200,
                            body: '{"code":"tnctjPitoGfygTEtK7bbPnSHN5Esdfdo4eeJbE2vvuSa3cYjp8vRetkXVKKLk07E"}',
                            effectiveUri: rtrim($this->account->server_url, '/').'/api/v1/auth/login',
                            headers: [],
                        );
                    }

                    public function putJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        return $this->postJson($uri, $payload, $options);
                    }

                    public function persist(): void {}
                };
            }
        };
    });

    app(SyncAccountOverview::class)->handle($account->fresh());

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Active);
    expect(Village::query()->where('travian_village_id', '23378')->exists())->toBeTrue();
    expect(Village::query()->first()?->buildings()->where('slot_id', 26)->first()?->building_gid)->toBe(15);
});

test('sync account overview preserves a paused account without reactivating it', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
        'username' => 'marshal',
        'password' => 'secret',
        'status' => AccountStatus::Paused,
        'is_active' => false,
    ]);

    $dorf1Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf1.php.html'));
    $dorf2Html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf2.php.html'));

    expect($dorf1Html)->not->toBeFalse();
    expect($dorf2Html)->not->toBeFalse();

    app()->bind(AccountSessionFactory::class, function () use ($account, $dorf1Html, $dorf2Html) {
        return new class($account, (string) $dorf1Html, (string) $dorf2Html) implements AccountSessionFactory
        {
            public function __construct(
                protected Account $account,
                protected string $dorf1Html,
                protected string $dorf2Html,
            ) {}

            public function for(Account $account): AccountSession
            {
                return new class($this->account, $this->dorf1Html, $this->dorf2Html) implements AccountSession
                {
                    public function __construct(
                        protected Account $account,
                        protected string $dorf1Html,
                        protected string $dorf2Html,
                    ) {}

                    public function get(string $uri, array $options = []): SessionResponse
                    {
                        $body = str_contains($uri, '/dorf2.php') ? $this->dorf2Html : $this->dorf1Html;
                        $effectiveUri = rtrim($this->account->server_url, '/').(str_contains($uri, '/dorf2.php') ? '/dorf2.php' : '/dorf1.php');

                        return new SessionResponse(
                            statusCode: 200,
                            body: $body,
                            effectiveUri: $effectiveUri,
                            headers: [],
                        );
                    }

                    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
                    {
                        return new SessionResponse(
                            statusCode: 200,
                            body: $this->dorf1Html,
                            effectiveUri: rtrim($this->account->server_url, '/').'/dorf1.php',
                            headers: [],
                        );
                    }

                    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        return new SessionResponse(
                            statusCode: 200,
                            body: $this->dorf1Html,
                            effectiveUri: rtrim($this->account->server_url, '/').'/dorf1.php',
                            headers: [],
                        );
                    }

                    public function putJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        return $this->postJson($uri, $payload, $options);
                    }

                    public function persist(): void {}
                };
            }
        };
    });

    app(SyncAccountOverview::class)->handle($account->fresh());

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Paused);
    expect($account->is_active)->toBeFalse();
});

test('sync account overview schedules a connection retry after a curl timeout', function () {
    Carbon::setTestNow(now()->startOfSecond());

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
        'username' => 'marshal',
        'password' => 'secret',
        'status' => AccountStatus::Active,
        'is_active' => true,
    ]);

    app()->bind(AccountSessionFactory::class, function () {
        return new class implements AccountSessionFactory
        {
            public function for(Account $account): AccountSession
            {
                return new class implements AccountSession
                {
                    public function get(string $uri, array $options = []): SessionResponse
                    {
                        throw new RuntimeException('cURL error 28: Connection timed out after 10010 milliseconds');
                    }

                    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
                    {
                        throw new RuntimeException('cURL error 28: Connection timed out after 10010 milliseconds');
                    }

                    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        throw new RuntimeException('cURL error 28: Connection timed out after 10010 milliseconds');
                    }

                    public function putJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        throw new RuntimeException('cURL error 28: Connection timed out after 10010 milliseconds');
                    }

                    public function persist(): void {}
                };
            }
        };
    });

    expect(fn () => app(SyncAccountOverview::class)->handle($account->fresh()))
        ->toThrow(AccountConnectionBackoffStarted::class);

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::ConnectionIssue);
    expect($account->connection_failure_count)->toBe(1);
    expect($account->connection_retry_after?->equalTo(now()->addMinute()))->toBeTrue();
    expect($account->last_connection_error_message)->toContain('cURL error 28');
    expect(ActivityLog::query()->where('status', 'failed')->where('message', 'like', 'Connection failed.%')->exists())->toBeTrue();

    Carbon::setTestNow();
});

test('sync account overview retries rejected login credentials before pausing the account', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);
    config()->set('travian.paths.landing', '/');

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
        'username' => 'missing-player',
        'password' => 'wrong-secret',
        'status' => AccountStatus::Active,
        'is_active' => true,
        'next_automation_at' => now(),
    ]);

    app()->bind(AccountSessionFactory::class, function () use ($account) {
        return new class($account) implements AccountSessionFactory
        {
            public function __construct(
                protected Account $account,
            ) {}

            public function for(Account $account): AccountSession
            {
                return new class($this->account) implements AccountSession
                {
                    public function __construct(
                        protected Account $account,
                    ) {}

                    public function get(string $uri, array $options = []): SessionResponse
                    {
                        return new SessionResponse(
                            statusCode: 200,
                            body: '<html><script src="/gpack/417.8/Variables.js"></script></html>',
                            effectiveUri: rtrim($this->account->server_url, '/').'/',
                            headers: [],
                        );
                    }

                    public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
                    {
                        return $this->postJson($uri, $formParams, $options);
                    }

                    public function postJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        return new SessionResponse(
                            statusCode: 401,
                            body: '{"error":"invalid credentials"}',
                            effectiveUri: rtrim($this->account->server_url, '/').$uri,
                            headers: [],
                        );
                    }

                    public function putJson(string $uri, array $payload, array $options = []): SessionResponse
                    {
                        return $this->postJson($uri, $payload, $options);
                    }

                    public function persist(): void {}
                };
            }
        };
    });

    app(SyncAccountOverview::class)->handle($account->fresh());

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Error)
        ->and($account->is_active)->toBeTrue()
        ->and($account->connection_failure_count)->toBe(1)
        ->and($account->connection_retry_after?->equalTo($now->copy()->addMinute()))->toBeTrue()
        ->and($account->next_automation_at?->equalTo($now->copy()->addMinute()))->toBeTrue()
        ->and($account->last_error_message)->toBe('Travian login API rejected the provided credentials or request context.');

    app(SyncAccountOverview::class)->handle($account->fresh());

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Error)
        ->and($account->is_active)->toBeTrue()
        ->and($account->connection_failure_count)->toBe(2)
        ->and($account->connection_retry_after?->equalTo($now->copy()->addMinutes(2)))->toBeTrue()
        ->and($account->next_automation_at?->equalTo($now->copy()->addMinutes(2)))->toBeTrue();

    app(SyncAccountOverview::class)->handle($account->fresh());

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Paused)
        ->and($account->is_active)->toBeFalse()
        ->and($account->connection_failure_count)->toBe(3)
        ->and($account->connection_retry_after)->toBeNull()
        ->and($account->next_automation_at)->toBeNull();

    expect(ActivityLog::query()->where('status', 'failed')->where('message', 'like', '%Retry 1/3 scheduled%')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('status', 'failed')->where('message', 'like', '%Retry 2/3 scheduled%')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('status', 'failed')->where('message', 'like', '%Account paused after 3 rejected login attempts.%')->exists())->toBeTrue();

    Carbon::setTestNow();
});
