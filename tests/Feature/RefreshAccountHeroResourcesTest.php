<?php

use App\Application\Accounts\Hero\RefreshAccountHeroResources;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manual Hero resource refresh reads Travian and preserves the existing Hero payload', function () {
    $account = Account::factory()->create(['server_url' => 'https://example.com']);
    $account->heroState()->create([
        'status' => 'home',
        'payload' => ['existing_key' => 'preserved'],
    ]);

    $session = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '<body class="village1"><div class="villageList"></div></body>', 'https://example.com/dorf1.php', []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return new SessionResponse(200, json_encode([
                'data' => ['ownPlayer' => [
                    'hero' => ['inventory' => [
                        ['id' => 1, 'amount' => 1200, 'typeId' => 145],
                        ['id' => 2, 'amount' => 2300, 'typeId' => 146],
                        ['id' => 3, 'amount' => 3400, 'typeId' => 147],
                        ['id' => 4, 'amount' => 4500, 'typeId' => 148],
                    ]],
                    'village' => ['id' => 991, 'resources' => []],
                ]],
            ], JSON_THROW_ON_ERROR), 'https://example.com/api/v1/graphql', []);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected.');
        }

        public function persist(): void {}
    };

    app()->instance(AccountSessionFactory::class, new class($session) implements AccountSessionFactory
    {
        public function __construct(private readonly AccountSession $session) {}

        public function for(Account $account): AccountSession
        {
            return $this->session;
        }
    });

    $resources = app(RefreshAccountHeroResources::class)->handle($account);
    $payload = $account->fresh('heroState')->heroState?->payload;

    expect($resources)->toBe(['wood' => 1200, 'clay' => 2300, 'iron' => 3400, 'crop' => 4500])
        ->and(data_get($payload, 'existing_key'))->toBe('preserved')
        ->and(data_get($payload, 'resource_inventory.wood'))->toBe(1200)
        ->and(data_get($payload, 'resource_inventory.reported_at'))->not->toBeNull();
});
