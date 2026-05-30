<?php

use App\Jobs\RunTravianAutomationJob;
use App\Models\Account;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('automation cycle queues one job for each due active non archived account', function () {
    Queue::fake();

    $activeAccount = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'next_automation_at' => now()->subMinute(),
    ]);
    Account::factory()->create([
        'is_active' => false,
        'is_archived' => false,
        'next_automation_at' => now()->subMinute(),
    ]);
    Account::factory()->create([
        'is_active' => true,
        'is_archived' => true,
        'next_automation_at' => now()->subMinute(),
    ]);
    Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'next_automation_at' => now()->addHour(),
    ]);

    $this->artisan('travian:automation-cycle')
        ->expectsOutput('Queued 1 smart automation job(s).')
        ->assertSuccessful();

    Queue::assertPushed(
        RunTravianAutomationJob::class,
        fn (RunTravianAutomationJob $job): bool => $job->accountId === $activeAccount->id,
    );
    Queue::assertPushed(RunTravianAutomationJob::class, 1);
    expect($activeAccount->fresh()->automation_dispatched_at)->not->toBeNull();
    expect($activeAccount->fresh()->next_automation_at)->not->toBeNull();
});

test('automation cycle does not queue jobs while global automation is disabled', function () {
    Queue::fake();
    SystemSetting::setAutomationEnabled(false);

    Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
    ]);

    $this->artisan('travian:automation-cycle')
        ->expectsOutput('Automation is disabled; no account automation jobs were queued.')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});
