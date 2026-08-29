<?php

use App\Enums\VillageTroopOrderStatus;
use App\Jobs\ExecuteVillageTroopOrderJob;
use App\Jobs\RefreshVillageTroopSnapshotJob;
use App\Livewire\Dashboard\Village\TroopOrders;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('component is namespaced beneath village and renders compact observed troop state', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);
    $village->buildings()->create(['slot_id' => 35, 'building_gid' => 13, 'building_type' => 'Smithy', 'current_level' => 5]);
    $village->troopSnapshot()->create([
        'units' => [
            '1' => ['research_state' => 'researched', 'training' => ['available' => true, 'max_trainable' => 617, 'smithy_level' => 1], 'smithy' => ['current_level' => 1]],
            '2' => ['research_state' => 'researched', 'training' => ['available' => true, 'max_trainable' => 1, 'smithy_level' => 0], 'smithy' => ['current_level' => 0]],
            '4' => ['research_state' => 'researched', 'training' => ['available' => true, 'max_trainable' => 2, 'smithy_level' => 0], 'smithy' => ['current_level' => 0]],
        ],
        'training_queues' => [],
        'research_queue' => [],
        'smithy_queue' => [],
        'pages' => [],
        'server_reported_at' => now(),
    ]);

    Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->assertSee('Legionnaire')
        ->assertSee('Praetorian')
        ->assertSee('Equites Legati')
        ->assertSeeHtml('unit.max_trainable')
        ->assertSee('Lv 1')
        ->assertDontSee('Crop 1')
        ->assertDontSee('Train automatically')
        ->assertDontSeeHtml('wire:model.change="selectedUnitId"')
        ->assertDontSeeHtml('wire:model="quantity"');

    Queue::assertNotPushed(RefreshVillageTroopSnapshotJob::class);
});

test('one-off Smithy improvement uses the same cancellable one minute order', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);
    $village->troopSnapshot()->create([
        'units' => [
            '1' => [
                'research_state' => 'researched',
                'training' => ['available' => true, 'max_trainable' => 12, 'smithy_level' => 1],
                'smithy' => ['available' => true, 'current_level' => 1, 'actionable' => true],
            ],
        ],
        'training_queues' => [],
        'research_queue' => [],
        'smithy_queue' => [],
        'pages' => [],
        'server_reported_at' => now(),
    ]);

    Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->assertSee('Improve to Lv 2')
        ->call('queueSmithyUpgrade', 1, true)
        ->assertHasNoErrors()
        ->assertSee('Improvement scheduled');

    $order = $village->troopOrders()->firstOrFail();

    expect($order->order_type)->toBe('smithy')
        ->and($order->target_level)->toBe(2)
        ->and($order->use_hero_resources)->toBeTrue()
        ->and($order->status)->toBe(VillageTroopOrderStatus::Scheduled);

    Queue::assertPushed(ExecuteVillageTroopOrderJob::class, fn (ExecuteVillageTroopOrderJob $job): bool => $job->orderId === $order->id);
});

test('Smithy shortage still exposes a hero-resource improvement order', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);
    $village->buildings()->create(['slot_id' => 35, 'building_gid' => 13, 'building_type' => 'Smithy', 'current_level' => 5]);
    $village->troopSnapshot()->create([
        'units' => [
            '1' => [
                'research_state' => 'researched',
                'training' => ['available' => true, 'max_trainable' => 0, 'smithy_level' => 1],
                'smithy' => [
                    'available' => true,
                    'current_level' => 1,
                    'actionable' => false,
                    'resource_shortage' => true,
                    'next_cost' => ['wood' => 1000, 'clay' => 900, 'iron' => 1200, 'crop' => 400],
                    'server_message' => 'Resources will be available later.',
                ],
            ],
        ],
        'training_queues' => [],
        'research_queue' => [],
        'smithy_queue' => [],
        'pages' => [],
        'server_reported_at' => now(),
    ]);

    Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->assertSee('Village resources are insufficient')
        ->assertSee('Wood')
        ->assertSee('1,000')
        ->assertSeeHtml('assets/res-icons/lumber_small.png')
        ->assertSeeHtml('sm:grid-cols-[minmax(11rem,1fr)_4.5rem_7.25rem]')
        ->assertSee('Schedule')
        ->assertSee('Schedule this training order')
        ->assertSee('Use hero resources & improve to Lv 2')
        ->assertSeeHtml('x-data="{ useHeroResources: false, submitting: false }"')
        ->call('queueSmithyUpgrade', 1, true)
        ->assertHasNoErrors();

    expect($village->troopOrders()->firstOrFail()->use_hero_resources)->toBeTrue();
});

test('Smithy cards explain building-level limits and the final troop level', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create(['travian_village_id' => '12345', 'name' => 'Roman Village', 'is_active' => true]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);
    $village->buildings()->create(['slot_id' => 35, 'building_gid' => 13, 'building_type' => 'Smithy', 'current_level' => 2]);
    $village->troopSnapshot()->create([
        'units' => [
            '1' => ['research_state' => 'researched', 'training' => ['available' => true, 'max_trainable' => 3], 'smithy' => ['available' => true, 'current_level' => 2, 'actionable' => false]],
            '2' => ['research_state' => 'researched', 'training' => ['available' => true, 'max_trainable' => 1], 'smithy' => ['available' => true, 'current_level' => 20, 'actionable' => false]],
        ],
        'training_queues' => [], 'research_queue' => [], 'smithy_queue' => [], 'pages' => [], 'server_reported_at' => now(),
    ]);

    Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->assertSee('Expand the Smithy')
        ->assertSee('maximum level 20 reached');
});

test('refresh completed in the same database second refreshes the troop row state', function () {
    Queue::fake();
    $reportedAt = now()->startOfSecond();
    $account = Account::factory()->create();
    $village = $account->villages()->create(['travian_village_id' => '12345', 'name' => 'Roman Village', 'is_active' => true]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => $reportedAt]);
    $village->troopSnapshot()->create([
        'units' => [], 'training_queues' => [], 'research_queue' => [], 'smithy_queue' => [], 'pages' => [],
        'server_reported_at' => $reportedAt,
    ]);

    Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->set('refreshRequestedAt', $reportedAt->toIso8601String())
        ->call('checkRefresh')
        ->assertSet('refreshRequestedAt', null)
        ->assertDispatched('troop-state-updated');
});

test('training building level is used when Smithy evidence is unavailable after demolition', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);
    $snapshot = $village->troopSnapshot()->create([
        'units' => [
            '1' => [
                'research_state' => 'researched',
                'training' => ['available' => true, 'max_trainable' => 12, 'smithy_level' => 4],
                'smithy' => ['available' => false, 'current_level' => null],
            ],
        ],
        'training_queues' => [],
        'research_queue' => [],
        'smithy_queue' => [],
        'pages' => ['smithy' => ['status' => 'missing_building']],
        'server_reported_at' => now(),
    ]);

    expect($snapshot->smithyLevelFor(1))->toBe(4);

    Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->assertSee('Legionnaire')
        ->assertSee('Lv 4');

    Queue::assertNotPushed(RefreshVillageTroopSnapshotJob::class);
});

test('training order is delayed for one minute and remains cancellable locally', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);
    $village->troopSnapshot()->create([
        'units' => ['2' => ['research_state' => 'researched', 'training' => ['available' => true, 'max_trainable' => 8, 'smithy_level' => 0]]],
        'training_queues' => [],
        'research_queue' => [],
        'smithy_queue' => [],
        'pages' => [],
        'server_reported_at' => now(),
    ]);

    $component = Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->call('queueTraining', 2, 100)
        ->assertHasNoErrors()
        ->assertSee('one minute');

    $order = $village->troopOrders()->firstOrFail();

    expect($order->status)->toBe(VillageTroopOrderStatus::Scheduled)
        ->and($order->requested_quantity)->toBe(100)
        ->and($order->execute_after->betweenIncluded(now()->addSeconds(55), now()->addSeconds(65)))->toBeTrue();

    Queue::assertPushed(ExecuteVillageTroopOrderJob::class, fn (ExecuteVillageTroopOrderJob $job): bool => $job->orderId === $order->id);

    $component->call('cancelOrder', $order->id);

    expect($order->fresh()->status)->toBe(VillageTroopOrderStatus::Cancelled)
        ->and($order->fresh()->cancelled_at)->not->toBeNull();
});

test('missing military snapshot requests a focused background refresh', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);

    Livewire::test(TroopOrders::class, ['villageId' => $village->id])
        ->assertSet('refreshRequestedAt', fn (?string $value): bool => $value !== null);

    Queue::assertPushed(RefreshVillageTroopSnapshotJob::class, fn (RefreshVillageTroopSnapshotJob $job): bool => $job->villageId === $village->id);
});
