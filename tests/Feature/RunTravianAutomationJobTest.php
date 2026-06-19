<?php

use App\Application\Accounts\Automation\PlanNextAccountAutomation;
use App\Application\Accounts\Construction\RunAccountAutomation;
use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Jobs\RunTravianAutomationJob;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('smart automation uses a fresh local snapshot without syncing first', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now(),
        'status' => AccountStatus::Error,
        'last_error_at' => now(),
        'last_error_message' => 'Previous queue failure.',
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Ready Village',
        'is_active' => true,
        'last_sync_at' => now(),
    ]);
    $village->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 10,
        'clay_production' => 10,
        'iron_production' => 10,
        'crop_production' => 10,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
        'simulated_at' => now(),
        'server_reported_at' => now(),
    ]);
    $village->runtimeState()->create([
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));

    $freshAccount = $account->fresh();

    expect($freshAccount->next_automation_at)->not->toBeNull();
    expect($freshAccount->automation_dispatched_at)->not->toBeNull();
    expect($freshAccount->status)->toBe(AccountStatus::Active);
    expect($freshAccount->last_error_at)->toBeNull();
    expect($freshAccount->last_error_message)->toBeNull();
});

test('village scoped automation updates dispatch and next automation timestamps', function () {
    $now = now()->startOfSecond();
    $nextAutomationAt = $now->copy()->addMinutes(7);

    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => $now,
        'next_automation_at' => $now->copy()->subHour(),
        'automation_dispatched_at' => $now->copy()->subHour(),
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '12346',
        'name' => 'Scoped Village',
        'is_active' => true,
        'last_sync_at' => $now,
    ]);
    $village->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 10,
        'clay_production' => 10,
        'iron_production' => 10,
        'crop_production' => 10,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
        'simulated_at' => $now,
        'server_reported_at' => $now,
    ]);
    $village->runtimeState()->create([
        'construction_entries' => [],
        'server_reported_at' => $now,
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class), $village->id);

    $planNextAccountAutomation = Mockery::mock(PlanNextAccountAutomation::class);
    $planNextAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class))
        ->andReturn($nextAutomationAt->toImmutable());

    (new RunTravianAutomationJob($account->id, $village->id, false, true))
        ->handle($syncAccountOverview, $runAccountAutomation, $planNextAccountAutomation);

    $freshAccount = $account->fresh();

    expect($freshAccount->automation_dispatched_at?->greaterThanOrEqualTo($now))->toBeTrue();
    expect($freshAccount->next_automation_at?->getTimestamp())->toBe($nextAutomationAt->getTimestamp());
});

test('smart automation does not refresh only because a complete snapshot is old', function () {
    config()->set('travian.automation.snapshot_stale_minutes', 10);

    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now()->subMinutes(30),
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Old But Complete Village',
        'is_active' => true,
        'last_sync_at' => now()->subMinutes(30),
    ]);
    $village->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 10,
        'clay_production' => 10,
        'iron_production' => 10,
        'crop_production' => 10,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
        'simulated_at' => now()->subMinutes(30),
        'server_reported_at' => now()->subMinutes(30),
    ]);
    $village->runtimeState()->create([
        'construction_entries' => [],
        'server_reported_at' => now()->subMinutes(30),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));
});

test('account automation refreshes when any active village snapshot is missing required data', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now(),
    ]);

    $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Fresh Village',
        'is_active' => true,
        'last_sync_at' => now(),
    ])->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 10,
        'clay_production' => 10,
        'iron_production' => 10,
        'crop_production' => 10,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
        'simulated_at' => now(),
        'server_reported_at' => now(),
    ]);
    $account->villages()->where('travian_village_id', '12345')->first()?->runtimeState()->create([
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $account->villages()->create([
        'travian_village_id' => '67890',
        'name' => 'Incomplete Village',
        'is_active' => true,
        'last_sync_at' => now(),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null);

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));
});

test('account automation refreshes when a stored construction timer has elapsed', function () {
    config()->set('travian.automation.snapshot_stale_minutes', 10);

    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now(),
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'قرية جديدة',
        'is_active' => true,
        'last_sync_at' => now(),
    ]);

    $village->runtimeState()->create([
        'construction_entries' => [
            [
                'building_name' => 'حقل القمح',
                'target_level' => 2,
                'remaining_seconds' => 300,
                'remaining_label' => '0:05:00',
                'finish_label' => '20:56',
            ],
        ],
        'server_reported_at' => now()->subSeconds(360),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null);

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));
});

test('account automation trusts per-entry recorded time for a newly queued construction', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now(),
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '26003',
        'name' => 'قرية عداد حديث',
        'is_active' => true,
        'last_sync_at' => now(),
    ]);

    $village->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 10,
        'clay_production' => 10,
        'iron_production' => 10,
        'crop_production' => 10,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
        'simulated_at' => now(),
        'server_reported_at' => now(),
    ]);

    $village->runtimeState()->create([
        'construction_entries' => [
            [
                'building_name' => 'المخبأ',
                'target_level' => 3,
                'remaining_seconds' => 600,
                'remaining_label' => '0:10:00',
                'finish_label' => '01:11',
                'recorded_at' => now()->toIso8601String(),
            ],
        ],
        'server_reported_at' => now()->subMinutes(30),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));
});

test('account automation refreshes when a stored village movement timer has elapsed', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now(),
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '26001',
        'name' => 'قرية حركة',
        'is_active' => true,
        'last_sync_at' => now(),
    ]);

    $village->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 10,
        'clay_production' => 10,
        'iron_production' => 10,
        'crop_production' => 10,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
        'simulated_at' => now(),
        'server_reported_at' => now(),
    ]);

    $village->runtimeState()->create([
        'construction_entries' => [],
        'movement_entries' => [
            [
                'kind' => 'outgoing',
                'label' => 'مغامرة',
                'remaining_seconds' => 300,
                'remaining_label' => '0:05:00',
            ],
        ],
        'server_reported_at' => now()->subSeconds(360),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null);

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));
});

test('account automation refreshes when an account hero timer has elapsed', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now(),
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '26002',
        'name' => 'قرية بطل',
        'is_active' => true,
        'last_sync_at' => now(),
    ]);

    $village->resourceState()->create([
        'wood' => 100,
        'clay' => 100,
        'iron' => 100,
        'crop' => 100,
        'wood_production' => 10,
        'clay_production' => 10,
        'iron_production' => 10,
        'crop_production' => 10,
        'warehouse_capacity' => 800,
        'granary_capacity' => 800,
        'simulated_at' => now(),
        'server_reported_at' => now(),
    ]);

    $village->runtimeState()->create([
        'construction_entries' => [],
        'movement_entries' => [],
        'server_reported_at' => now(),
    ]);

    $account->heroState()->create([
        'status' => 'adventure',
        'hero_remaining_seconds' => 300,
        'seen_at' => now()->subSeconds(360),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null);

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(Account::class), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));
});

test('account automation stops after sync schedules a retry window', function () {
    $retryAfter = now()->addMinutes(5);
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => null,
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null)
        ->andReturnUsing(function () use ($account, $retryAfter): void {
            $account->forceFill([
                'status' => AccountStatus::Error,
                'connection_retry_after' => $retryAfter,
                'next_automation_at' => $retryAfter,
            ])->save();
        });

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation->shouldNotReceive('handle');

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));

    expect($account->fresh()->next_automation_at?->getTimestamp())->toBe($retryAfter->getTimestamp());
});

test('account automation respects an active connection retry window even when dispatched as a chained job', function () {
    $retryAfter = now()->addMinutes(2);
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'status' => AccountStatus::ConnectionIssue,
        'connection_retry_after' => $retryAfter,
        'last_sync_at' => now(),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation->shouldNotReceive('handle');

    (new RunTravianAutomationJob($account->id, null, false, true))
        ->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));

    expect($account->fresh()->connection_retry_after?->getTimestamp())->toBe($retryAfter->getTimestamp());
});
