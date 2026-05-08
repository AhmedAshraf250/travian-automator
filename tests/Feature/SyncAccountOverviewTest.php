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

    $html = file_get_contents(base_path('may-help/travian-samples/dorf1.php.html'));

    expect($html)->not->toBeFalse();

    app()->bind(AccountSessionFactory::class, function () use ($account, $html) {
        return new class($account, (string) $html) implements AccountSessionFactory
        {
            public function __construct(
                protected Account $account,
                protected string $html,
            ) {}

            public function for(Account $account): AccountSession
            {
                return new class($this->account, $this->html) implements AccountSession
                {
                    public function __construct(
                        protected Account $account,
                        protected string $html,
                    ) {}

                    public function get(string $uri, array $options = []): SessionResponse
                    {
                        return new SessionResponse(
                            statusCode: 200,
                            body: $this->html,
                            effectiveUri: rtrim($this->account->server_url, '/').'/dorf1.php',
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
    expect($village?->resourceState?->wood)->toBe(504);
    expect($village?->resourceState?->warehouse_capacity)->toBe(800);
    expect(ActivityLog::query()->where('message', 'Account overview synced successfully from dorf1.')->exists())->toBeTrue();
});
