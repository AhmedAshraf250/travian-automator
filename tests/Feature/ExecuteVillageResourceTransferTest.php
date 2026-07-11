<?php

use App\Application\Accounts\Construction\Data\BuildPageAnalysis;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Trading\ExecuteVillageResourceTransfer;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('resource transfer skips low stock villages and confirms a rounded marketplace shipment', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $recipient = $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'population' => 90,
        'is_active' => true,
    ]);
    $recipient->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $recipient->resourceState()->create([
        'wood' => 1000,
        'clay' => 1000,
        'iron' => 1000,
        'crop' => 332,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 10000,
        'server_reported_at' => now(),
    ]);

    $lowStockSupplier = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'Largest low crop',
        'x' => 1,
        'y' => 1,
        'population' => 500,
        'is_active' => true,
    ]);
    $lowStockSupplier->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'send_enabled' => true,
        'send_min_resource_percentage' => 20,
        'send_reserve_resource_percentage' => 10,
    ]);
    $lowStockSupplier->resourceState()->create([
        'wood' => 6000,
        'clay' => 6000,
        'iron' => 6000,
        'crop' => 600,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 10000,
        'server_reported_at' => now(),
    ]);
    $lowStockSupplier->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 10,
    ]);

    $eligibleSupplier = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'Eligible supplier',
        'x' => 2,
        'y' => 2,
        'population' => 300,
        'is_active' => true,
    ]);
    $eligibleSupplier->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'send_enabled' => true,
        'send_min_resource_percentage' => 10,
        'send_reserve_resource_percentage' => 10,
    ]);
    $eligibleSupplier->resourceState()->create([
        'wood' => 5000,
        'clay' => 5000,
        'iron' => 5000,
        'crop' => 1500,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 10000,
        'server_reported_at' => now(),
    ]);
    $eligibleSupplier->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 10,
    ]);

    $analysis = new BuildPageAnalysis(
        actionUri: null,
        requiredResources: [
            'wood' => 310,
            'clay' => 780,
            'iron' => 390,
            'crop' => 465,
        ],
        blockedReason: 'resource_shortage',
        blockedMessage: 'resources missing',
        resourceReadySeconds: 1329,
        resourceReadyLabel: '0:22:09',
    );

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $getRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $jsonRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $putRequests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->getRequests[] = $uri;

            return new SessionResponse(
                statusCode: 200,
                body: '<body></body>',
                effectiveUri: 'https://example.com'.(str_starts_with($uri, '/') ? $uri : '/'.$uri),
                headers: [],
            );
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during resource transfer.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->jsonRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            if ($uri === '/api/v1/graphql') {
                return new SessionResponse(
                    statusCode: 200,
                    body: '{"data":{"ownPlayer":{"village":{"marketplace":{"merchantsInfo":{"capacity":500,"available":9}}}}}}',
                    effectiveUri: 'https://example.com/api/v1/graphql',
                    headers: [],
                );
            }

            return new SessionResponse(
                statusCode: 200,
                body: '',
                effectiveUri: 'https://example.com'.$uri,
                headers: [],
            );
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->putRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(
                statusCode: 200,
                body: '{"duration":225,"merchantsAmount":1,"runs":1}',
                effectiveUri: 'https://example.com'.$uri,
                headers: [
                    'x-nonce' => ['nonce-123'],
                ],
            );
        }

        public function persist(): void {}
    };

    app(ExecuteVillageResourceTransfer::class)->handle(
        $account->fresh(),
        $recipient->fresh(),
        $session,
        [
            'queue_kind' => 'field',
            'slot_id' => 1,
            'target_level' => 6,
        ],
        $analysis,
    );

    $transferLog = ActivityLog::query()
        ->where('activity_type', ActivityType::Transfer)
        ->where('status', 'done')
        ->latest('id')
        ->first();

    expect($session->getRequests)->toBe([
        '/dorf1.php?newdid=23379',
        '/dorf2.php',
        '/build.php?id=32&gid=17',
        '/build.php?id=32&gid=17&t=5',
    ]);
    expect($session->putRequests)->toHaveCount(1)
        ->and($session->putRequests[0]['uri'])->toBe('/api/v1/marketplace/resources/send')
        ->and($session->putRequests[0]['payload']['resources'])->toBe([
            'lumber' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 200,
        ])
        ->and($session->putRequests[0]['payload']['destination'])->toBe([
            'x' => 9,
            'y' => 59,
        ]);
    expect($session->jsonRequests)->toHaveCount(2)
        ->and($session->jsonRequests[1]['uri'])->toBe('/api/v1/marketplace/resources/send')
        ->and($session->jsonRequests[1]['options']['headers']['x-nonce'] ?? null)->toBe('nonce-123');
    expect($eligibleSupplier->fresh()->resourceState?->crop)->toBe(1300);
    expect($lowStockSupplier->fresh()->resourceState?->crop)->toBe(600);
    expect($transferLog?->payload['resources'])->toBe([
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 200,
    ]);
});

test('resource transfer stops before confirmation when merchant travel time exceeds the limit', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $recipient = $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'population' => 90,
        'is_active' => true,
    ]);
    $recipient->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $recipient->resourceState()->create([
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 10000,
        'server_reported_at' => now(),
    ]);

    $supplier = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'Supplier',
        'x' => 2,
        'y' => 2,
        'population' => 300,
        'is_active' => true,
    ]);
    $supplier->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'send_enabled' => true,
        'send_min_resource_percentage' => 0,
        'send_reserve_resource_percentage' => 0,
        'trade_max_duration_seconds' => 300,
    ]);
    $supplier->resourceState()->create([
        'wood' => 1000,
        'clay' => 1000,
        'iron' => 1000,
        'crop' => 1000,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 10000,
        'server_reported_at' => now(),
    ]);
    $supplier->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 10,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $getRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $jsonRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $putRequests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->getRequests[] = $uri;

            return new SessionResponse(200, '<body></body>', 'https://example.com'.(str_starts_with($uri, '/') ? $uri : '/'.$uri), []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during resource transfer.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->jsonRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            if ($uri === '/api/v1/graphql') {
                return new SessionResponse(200, '{"data":{"ownPlayer":{"village":{"marketplace":{"merchantsInfo":{"capacity":500,"available":2}}}}}}', 'https://example.com/api/v1/graphql', []);
            }

            throw new RuntimeException('Confirmation request was not expected after duration limit.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->putRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(200, '{"duration":301,"merchantsAmount":1,"runs":1}', 'https://example.com'.$uri, [
                'x-nonce' => ['nonce-123'],
            ]);
        }

        public function persist(): void {}
    };

    app(ExecuteVillageResourceTransfer::class)->handle(
        $account->fresh(),
        $recipient->fresh(),
        $session,
        ['queue_kind' => 'building'],
        new BuildPageAnalysis(
            actionUri: null,
            requiredResources: ['wood' => 500, 'clay' => 0, 'iron' => 0, 'crop' => 0],
            blockedReason: 'resource_shortage',
            blockedMessage: 'wood missing',
            resourceReadySeconds: null,
            resourceReadyLabel: null,
        ),
    );

    expect($session->putRequests)->toHaveCount(1);
    expect($session->jsonRequests)->toHaveCount(1);
    expect($supplier->fresh()->resourceState?->wood)->toBe(1000);
    expect(ActivityLog::query()
        ->where('activity_type', ActivityType::Transfer)
        ->where('message', 'Resource transfer stopped: merchant travel time is above the configured limit.')
        ->exists())->toBeTrue();
    expect(ActivityLog::query()->where('activity_type', ActivityType::Transfer)->latest('id')->value('message'))
        ->toBe('Resource transfer stopped: merchant travel time is above the configured limit.');
});

test('resource transfer does not send resources to villages without supply enabled', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $recipient = $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'population' => 90,
        'is_active' => true,
    ]);
    $recipient->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'support_enabled' => false,
    ]);
    $recipient->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 10000,
        'server_reported_at' => now(),
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $getRequests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->getRequests[] = $uri;

            return new SessionResponse(200, '', 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during resource transfer.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during resource transfer.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during resource transfer.');
        }

        public function persist(): void {}
    };

    app(ExecuteVillageResourceTransfer::class)->handle(
        $account->fresh(),
        $recipient->fresh(),
        $session,
        ['queue_kind' => 'building'],
        new BuildPageAnalysis(
            actionUri: null,
            requiredResources: ['wood' => 500, 'clay' => 100, 'iron' => 100, 'crop' => 100],
            blockedReason: 'resource_shortage',
            blockedMessage: 'resources missing',
            resourceReadySeconds: null,
            resourceReadyLabel: null,
        ),
    );

    expect($session->getRequests)->toBe([]);
    expect(ActivityLog::query()->where('activity_type', ActivityType::Transfer)->latest('id')->value('message'))
        ->toBe('Recipient village is not configured to receive support resources.');
});

test('resource transfer respects disabled negative crop feeding', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $recipient = $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'population' => 90,
        'is_active' => true,
    ]);
    $recipient->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'supply_negative_crop_enabled' => false,
    ]);
    $recipient->resourceState()->create([
        'wood' => 1000,
        'clay' => 1000,
        'iron' => 1000,
        'crop' => 0,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => -120,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 10000,
        'server_reported_at' => now(),
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $getRequests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->getRequests[] = $uri;

            return new SessionResponse(200, '', 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during resource transfer.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during resource transfer.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during resource transfer.');
        }

        public function persist(): void {}
    };

    app(ExecuteVillageResourceTransfer::class)->handle(
        $account->fresh(),
        $recipient->fresh(),
        $session,
        ['queue_kind' => 'field'],
        new BuildPageAnalysis(
            actionUri: null,
            requiredResources: ['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 400],
            blockedReason: 'resource_shortage',
            blockedMessage: 'crop missing',
            resourceReadySeconds: null,
            resourceReadyLabel: null,
        ),
    );

    expect($session->getRequests)->toBe([]);
    expect(ActivityLog::query()->where('activity_type', ActivityType::Transfer)->exists())->toBeFalse();
});

test('resource transfer feeds villages with dangerous negative crop production', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $recipient = $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'Hungry village',
        'x' => 9,
        'y' => 59,
        'population' => 90,
        'is_active' => true,
    ]);
    $recipient->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'support_enabled' => true,
        'supply_negative_crop_enabled' => true,
    ]);
    $recipient->resourceState()->create([
        'wood' => 1000,
        'clay' => 1000,
        'iron' => 1000,
        'crop' => 100,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => -100,
        'warehouse_capacity' => 10000,
        'granary_capacity' => 1000,
        'server_reported_at' => now(),
    ]);

    $supplier = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'Crop supplier',
        'x' => 2,
        'y' => 2,
        'population' => 300,
        'is_active' => true,
    ]);
    $supplier->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'send_enabled' => true,
        'send_min_resource_percentage' => 0,
        'send_reserve_resource_percentage' => 0,
    ]);
    $supplier->resourceState()->create([
        'wood' => 500,
        'clay' => 500,
        'iron' => 500,
        'crop' => 1000,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 1000,
        'granary_capacity' => 1000,
        'server_reported_at' => now(),
    ]);
    $supplier->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 10,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $getRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $jsonRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $putRequests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->getRequests[] = $uri;

            return new SessionResponse(200, '<body></body>', 'https://example.com'.(str_starts_with($uri, '/') ? $uri : '/'.$uri), []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during resource transfer.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->jsonRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            if ($uri === '/api/v1/graphql') {
                return new SessionResponse(200, '{"data":{"ownPlayer":{"village":{"marketplace":{"merchantsInfo":{"capacity":500,"available":1}}}}}}', 'https://example.com/api/v1/graphql', []);
            }

            return new SessionResponse(200, '', 'https://example.com'.$uri, []);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->putRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(200, '{"duration":225,"merchantsAmount":1,"runs":1}', 'https://example.com'.$uri, [
                'x-nonce' => ['nonce-123'],
            ]);
        }

        public function persist(): void {}
    };

    app(ExecuteVillageResourceTransfer::class)->supportNegativeCrop($account->fresh(), $recipient->fresh(), $session);

    expect($session->putRequests)->toHaveCount(1)
        ->and($session->putRequests[0]['payload']['resources'])->toBe([
            'lumber' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 500,
        ])
        ->and($supplier->fresh()->resourceState?->crop)->toBe(500);
    expect(ActivityLog::query()->where('activity_type', ActivityType::Transfer)->latest('id')->value('message'))
        ->toBe('Resource transfer sent for negative crop support.');
});
