<?php

use App\Application\Accounts\Automation\PlanNextAccountAutomation;
use App\Application\Accounts\Construction\RunAccountAutomation;
use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Jobs\RunTravianAutomationJob;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('smart automation uses a fresh local snapshot without syncing first', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now(),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    $runAccountAutomation = Mockery::mock(RunAccountAutomation::class);
    $runAccountAutomation
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null);

    (new RunTravianAutomationJob($account->id))->handle($syncAccountOverview, $runAccountAutomation, app(PlanNextAccountAutomation::class));

    expect($account->fresh()->next_automation_at)->not->toBeNull();
});

test('smart automation refreshes a stale snapshot before running automation', function () {
    config()->set('travian.automation.snapshot_stale_minutes', 10);

    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'last_sync_at' => now()->subMinutes(30),
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

test('account automation refreshes when any active village snapshot is stale', function () {
    config()->set('travian.automation.snapshot_stale_minutes', 10);

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
    ]);

    $account->villages()->create([
        'travian_village_id' => '67890',
        'name' => 'Old Village',
        'is_active' => true,
        'last_sync_at' => now()->subMinutes(30),
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
