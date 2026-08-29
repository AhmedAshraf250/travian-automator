<?php

use App\Application\Accounts\Hero\UseHeroResourcesForCost;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Troops\ExecuteVillageTroopOrder;
use App\Enums\ActivityType;
use App\Enums\VillageTroopOrderStatus;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('one-off order submits once and records Travian partial acceptance', function () {
    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);
    $village->buildings()->create([
        'slot_id' => 34,
        'building_gid' => 19,
        'building_type' => 'Barracks',
        'current_level' => 5,
    ]);
    $order = $village->troopOrders()->create([
        'unit_id' => 1,
        'requested_quantity' => 100,
        'status' => VillageTroopOrderStatus::Scheduled,
        'execute_after' => now()->subSecond(),
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<array<string, mixed>> */
        public array $posts = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            return new SessionResponse(200, orderTrainingPageHtml(0), 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            $this->posts[] = ['uri' => $uri, 'form' => $formParams];

            return new SessionResponse(200, orderTrainingPageHtml(12), 'https://example.com/build.php?id=34&gid=19', []);
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

    app(ExecuteVillageTroopOrder::class)->handle($account, $order, $session);

    expect($session->posts)->toHaveCount(1)
        ->and($session->posts[0]['form'])->toMatchArray([
            'action' => 'trainTroops',
            'checksum' => 'dynamic-checksum',
            't1' => '100',
            's1' => 'ok',
        ])
        ->and($order->fresh()->status)->toBe(VillageTroopOrderStatus::Submitted)
        ->and($order->fresh()->accepted_quantity)->toBe(12);

    expect(ActivityLog::query()->where('activity_type', ActivityType::Train)->value('payload'))->toMatchArray([
        'order_id' => $order->id,
        'unit_id' => 1,
        'requested_quantity' => 100,
        'accepted_quantity' => 12,
    ]);
});

test('one-off Smithy order submits the dynamic action and confirms its queue', function () {
    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create(['travian_village_id' => '12345', 'name' => 'Roman Village', 'is_active' => true]);
    $village->buildings()->create(['slot_id' => 35, 'building_gid' => 13, 'building_type' => 'Smithy', 'current_level' => 4]);
    $order = $village->troopOrders()->create([
        'unit_id' => 1,
        'order_type' => 'smithy',
        'requested_quantity' => 1,
        'target_level' => 2,
        'status' => VillageTroopOrderStatus::Scheduled,
        'execute_after' => now()->subSecond(),
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $gets = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->gets[] = $uri;
            $html = str_contains($uri, 'action=research') ? smithyOrderHtml(true) : smithyOrderHtml(false);

            return new SessionResponse(200, $html, 'https://example.com'.$uri, []);
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

    app(ExecuteVillageTroopOrder::class)->handle($account, $order, $session);

    expect($session->gets)->toHaveCount(2)
        ->and($session->gets[1])->toContain('action=research')
        ->and($order->fresh()->status)->toBe(VillageTroopOrderStatus::Submitted)
        ->and($order->fresh()->result_message)->toContain('level 2');
});

test('Smithy order uses hero resources only for a structurally verified shortage', function () {
    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create(['travian_village_id' => '12345', 'name' => 'Roman Village', 'is_active' => true]);
    $village->buildings()->create(['slot_id' => 35, 'building_gid' => 13, 'building_type' => 'Smithy', 'current_level' => 4]);
    $order = $village->troopOrders()->create([
        'unit_id' => 1,
        'order_type' => 'smithy',
        'requested_quantity' => 1,
        'target_level' => 2,
        'use_hero_resources' => true,
        'status' => VillageTroopOrderStatus::Scheduled,
        'execute_after' => now()->subSecond(),
    ]);

    $heroResources = Mockery::mock(UseHeroResourcesForCost::class);
    $heroResources->shouldReceive('handleCost')->once()->andReturnTrue();
    app()->instance(UseHeroResourcesForCost::class, $heroResources);

    $session = new class implements AccountSession
    {
        public int $reads = 0;

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->reads++;
            $html = match ($this->reads) {
                1 => smithyShortageHtml(),
                2 => smithyOrderHtml(false),
                default => smithyOrderHtml(true),
            };

            return new SessionResponse(200, $html, 'https://example.com'.$uri, []);
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

    app(ExecuteVillageTroopOrder::class)->handle($account, $order, $session);

    expect($session->reads)->toBe(3)
        ->and($order->fresh()->status)->toBe(VillageTroopOrderStatus::Submitted);
});

function orderTrainingPageHtml(int $queuedQuantity): string
{
    $queue = $queuedQuantity > 0
        ? '<table class="under_progress"><tbody><tr><td><img class="unit u1"></td><td class="desc">'.$queuedQuantity.' Legionnaire</td><td class="dur"><span class="timer" value="600">0:10:00</span></td></tr></tbody></table>'
        : '';

    return '<form action="/build.php?id=34&amp;gid=19"><input type="hidden" name="action" value="trainTroops"><input type="hidden" name="checksum" value="dynamic-checksum"><div class="action troop troopt1"><img class="unit u1"><div data-troopID="1"><input name="t1"></div><a onclick="$(\'.val(12)\')">12</a></div></form>'.$queue;
}

function smithyOrderHtml(bool $queued): string
{
    $queue = $queued
        ? '<table class="under_progress"><tbody><tr><td><img class="unit u1"></td><td class="level">Level 1 → 2</td><td><span class="timer" value="600">0:10:00</span></td></tr></tbody></table>'
        : '';

    return '<div class="build_details researches"><div class="research"><img class="unit u1"><div class="title"><span class="level">Level 1</span></div><a href="/build.php?id=35&amp;gid=13&amp;action=research&amp;t=t1&amp;checksum=smithy-token">Improve</a></div></div>'.$queue;
}

function smithyShortageHtml(): string
{
    return '<div class="build_details researches"><div class="research"><img class="unit u1"><div class="title"><span class="level">Level 1</span></div><div class="resourceWrapper charges"><span class="resource transfer fillUp"><i class="r1Big"></i><span class="value">900</span></span></div><div class="cta"><div class="errorMessage">Resources later</div></div></div></div>';
}
