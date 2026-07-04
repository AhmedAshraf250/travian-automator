<?php

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Trading\ExecuteManualMarketplaceTransfer;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manual marketplace transfer decrements local merchants and logs shipment resources', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $sourceVillage = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $sourceVillage->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $sourceVillage->resourceState()->create([
        'wood' => 1000,
        'clay' => 1000,
        'iron' => 1000,
        'crop' => 1000,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 6300,
        'granary_capacity' => 6300,
        'available_merchants' => 10,
        'merchant_capacity' => 500,
        'server_reported_at' => now(),
    ]);
    $sourceVillage->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 10,
    ]);
    $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'is_active' => true,
    ]);

    $session = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            return new SessionResponse(200, '<body class="village1"></body>', 'https://example.com'.(str_starts_with($uri, '/') ? $uri : '/'.$uri), []);
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
            return new SessionResponse(200, '{"duration":60}', 'https://example.com'.$uri, [
                'x-nonce' => ['nonce-transfer'],
            ]);
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

    (new ExecuteManualMarketplaceTransfer($factory, new TravianLoginAction))
        ->handle($account, $sourceVillage, 9, 59, [
            'wood' => 0,
            'clay' => 500,
            'iron' => 0,
            'crop' => 0,
        ]);

    $resourceState = $sourceVillage->resourceState()->first();
    $log = ActivityLog::query()
        ->where('activity_type', ActivityType::Transfer)
        ->where('status', ActivityLogStatus::Done)
        ->latest('id')
        ->first();

    expect($resourceState?->clay)->toBe(500)
        ->and($resourceState?->available_merchants)->toBe(9)
        ->and($log?->message)->toBe('Manual marketplace transfer sent successfully: 500 clay to CR7 [9|59].')
        ->and($log?->payload['destination']['name'] ?? null)->toBe('CR7')
        ->and($log?->payload['merchants_used'] ?? null)->toBe(1);
});
