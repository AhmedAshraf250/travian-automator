<?php

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sync account overview stores villages and resource state from dorf1 html', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
        'username' => 'marshal',
        'password' => 'secret',
        'status' => AccountStatus::Paused,
    ]);

    $dorf1Html = file_get_contents(base_path('may-help/travian-samples/dorf1.php.html'));
    $dorf2Html = file_get_contents(base_path('may-help/travian-samples/dorf2.php.html'));

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
    expect(ActivityLog::query()->where('message', 'Account overview synced successfully from dorf1 and dorf2.')->exists())->toBeTrue();
});

test('sync account overview can authenticate through the modern login api flow', function () {
    config()->set('travian.paths.landing', '/');

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
        'username' => 'marshal',
        'password' => 'secret',
        'status' => AccountStatus::Paused,
    ]);

    $loginHtml = file_get_contents(base_path('may-help/travian-samples/login.html'));
    $dorf1Html = file_get_contents(base_path('may-help/travian-samples/dorf1.php.html'));
    $dorf2Html = file_get_contents(base_path('may-help/travian-samples/dorf2.php.html'));

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

    $dorf1Html = file_get_contents(base_path('may-help/travian-samples/dorf1.php.html'));
    $dorf2Html = file_get_contents(base_path('may-help/travian-samples/dorf2.php.html'));

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
