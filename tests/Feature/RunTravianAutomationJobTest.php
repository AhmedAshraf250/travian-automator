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
    expect($freshAccount->status)->toBe(AccountStatus::Active);
    expect($freshAccount->last_error_at)->toBeNull();
    expect($freshAccount->last_error_message)->toBeNull();
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
