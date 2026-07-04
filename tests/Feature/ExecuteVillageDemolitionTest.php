<?php

use App\Application\Accounts\Construction\ExecuteVillageDemolition;
use App\Application\Accounts\Construction\RefreshVillageDemolitionSnapshot;
use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demolition snapshot persistence accepts immutable timestamps from the application clock', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'is_active' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);

    $snapshot = app(RefreshVillageDemolitionSnapshot::class)->persistSnapshot($village, [
        'main_building_level' => 10,
        'available_buildings' => [
            ['slot_id' => 21, 'name' => 'المخبأ', 'level' => 7],
        ],
        'active' => [
            'name' => 'المخبأ',
            'target_level' => 6,
            'remaining_seconds' => 350,
            'cancel_uri' => '/build.php?gid=15&del=932338',
        ],
    ]);

    expect($snapshot['active']['target_level'] ?? null)->toBe(6)
        ->and($village->runtimeState()->first()?->demolition_entries['active']['cancel_uri'] ?? null)
        ->toBe('/build.php?gid=15&del=932338');
});

test('demolition treats a non successful response as started when main building snapshot confirms it', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'is_active' => true,
    ]);
    $mainBuilding = $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);
    $village->buildings()->create([
        'slot_id' => 21,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 7,
    ]);

    $session = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '<body class="village1"></body>', 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            return $this->postJson($uri, $formParams, $options);
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return new SessionResponse(403, '{"error":"already-started"}', 'https://example.com'.$uri, []);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->postJson($uri, $payload, $options);
        }

        public function persist(): void {}
    };

    $factory = new class($session) implements AccountSessionFactory
    {
        public function __construct(private AccountSession $session) {}

        public function for(Account $account): AccountSession
        {
            return $this->session;
        }
    };

    $snapshotRefresh = Mockery::mock(RefreshVillageDemolitionSnapshot::class);
    $snapshotRefresh
        ->shouldReceive('openMainBuilding')
        ->once()
        ->andReturn(new SessionResponse(200, '<body></body>', 'https://example.com/build.php?id=26&gid=15', []));
    $snapshotRefresh
        ->shouldReceive('handle')
        ->once()
        ->andReturn([
            'main_building_level' => 10,
            'available_buildings' => [
                ['slot_id' => 21, 'name' => 'المخبأ', 'level' => 7],
            ],
            'active' => [
                'name' => 'المخبأ',
                'target_level' => 6,
                'remaining_seconds' => 350,
            ],
        ]);

    (new ExecuteVillageDemolition($factory, new TravianLoginAction, $snapshotRefresh))
        ->demolish($account, $village, 21);

    expect(ActivityLog::query()
        ->where('activity_type', ActivityType::Manual)
        ->where('status', ActivityLogStatus::Done)
        ->where('message', 'Building demolition started.')
        ->exists())->toBeTrue();
    expect(ActivityLog::query()
        ->where('activity_type', ActivityType::Manual)
        ->where('status', ActivityLogStatus::Failed)
        ->where('message', 'Building demolition was rejected by Travian.')
        ->exists())->toBeFalse();
});

test('demolition cancel treats a non successful response as cancelled when snapshot has no active demolition', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'is_active' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);

    $session = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            if ($uri === '/build.php?gid=15&del=932338') {
                return new SessionResponse(403, '{"error":"already-cancelled"}', 'https://example.com'.$uri, []);
            }

            return new SessionResponse(200, '<body class="village1"></body>', 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            return $this->postJson($uri, $formParams, $options);
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '{"success":true}', 'https://example.com'.$uri, []);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->postJson($uri, $payload, $options);
        }

        public function persist(): void {}
    };

    $factory = new class($session) implements AccountSessionFactory
    {
        public function __construct(private AccountSession $session) {}

        public function for(Account $account): AccountSession
        {
            return $this->session;
        }
    };

    $snapshotRefresh = Mockery::mock(RefreshVillageDemolitionSnapshot::class);
    $snapshotRefresh
        ->shouldReceive('openMainBuilding')
        ->once()
        ->andReturn(new SessionResponse(200, '<body></body>', 'https://example.com/build.php?id=26&gid=15', []));
    $snapshotRefresh
        ->shouldReceive('documentRequestOptions')
        ->once()
        ->andReturn(['headers' => []]);
    $snapshotRefresh
        ->shouldReceive('handle')
        ->once()
        ->andReturn([
            'main_building_level' => 10,
            'available_buildings' => [],
            'active' => null,
        ]);

    (new ExecuteVillageDemolition($factory, new TravianLoginAction, $snapshotRefresh))
        ->cancel($account, $village, '/build.php?gid=15&del=932338');

    expect(ActivityLog::query()
        ->where('activity_type', ActivityType::Manual)
        ->where('status', ActivityLogStatus::Done)
        ->where('message', 'Building demolition cancelled.')
        ->exists())->toBeTrue();
    expect(ActivityLog::query()
        ->where('activity_type', ActivityType::Manual)
        ->where('status', ActivityLogStatus::Failed)
        ->where('message', 'Building demolition cancel was rejected by Travian.')
        ->exists())->toBeFalse();
});
