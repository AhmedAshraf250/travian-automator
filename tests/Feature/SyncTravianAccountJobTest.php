<?php

use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Jobs\RunTravianAutomationJob;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\WithoutOverlapping;

uses(RefreshDatabase::class);

test('sync job timeout is configurable and outlives slow proxy requests', function () {
    config()->set('travian.automation.job_timeout_seconds', 120);

    $job = new SyncTravianAccountJob(123);

    expect($job->timeout)->toBe(120);
});

test('account jobs use bounded locks without replaying remote automation', function () {
    config()->set('travian.automation.job_timeout_seconds', 300);
    config()->set('travian.automation.account_lock_release_seconds', 60);
    config()->set('travian.automation.account_lock_expire_seconds', 360);

    $syncJob = new SyncTravianAccountJob(123);
    $automationJob = new RunTravianAutomationJob(123);
    $syncMiddleware = $syncJob->middleware()[0];
    $automationMiddleware = $automationJob->middleware()[0];

    expect($syncMiddleware)->toBeInstanceOf(WithoutOverlapping::class)
        ->and($syncJob->tries)->toBe(0)
        ->and($syncJob->maxExceptions)->toBe(1)
        ->and($syncJob->failOnTimeout)->toBeTrue()
        ->and($syncMiddleware->releaseAfter)->toBe(60)
        ->and($syncMiddleware->expiresAfter)->toBe(360)
        ->and($automationMiddleware)->toBeInstanceOf(WithoutOverlapping::class)
        ->and($automationJob->tries)->toBe(1)
        ->and($automationJob->failOnTimeout)->toBeTrue()
        ->and($automationMiddleware->releaseAfter)->toBeNull()
        ->and($automationMiddleware->expiresAfter)->toBe(360);
});

test('database queue retry window outlives the Travian job timeout', function () {
    expect((int) config('queue.connections.database.retry_after'))
        ->toBeGreaterThan((int) config('travian.automation.job_timeout_seconds'));
});

test('sync job does not send requests for a paused account', function () {
    $account = Account::factory()->create([
        'is_active' => false,
        'is_archived' => false,
        'status' => AccountStatus::Paused,
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    (new SyncTravianAccountJob($account->id))->handle($syncAccountOverview);

    expect(ActivityLog::query()->count())->toBe(0);
});

test('sync job does not send requests when global automation is off', function () {
    SystemSetting::setAutomationEnabled(false);

    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'status' => AccountStatus::Active,
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    (new SyncTravianAccountJob($account->id))->handle($syncAccountOverview);

    expect(ActivityLog::query()->count())->toBe(0);
});

test('sync job does not send requests while account is waiting for connection retry', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'status' => AccountStatus::ConnectionIssue,
        'connection_retry_after' => now()->addMinutes(10),
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview->shouldNotReceive('handle');

    (new SyncTravianAccountJob($account->id))->handle($syncAccountOverview);

    expect(ActivityLog::query()->count())->toBe(0);
});

test('sync job passes reload auto preference to sync service', function () {
    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'status' => AccountStatus::Active,
    ]);

    $syncAccountOverview = Mockery::mock(SyncAccountOverview::class);
    $syncAccountOverview
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (Account $passedAccount): bool => $passedAccount->is($account)), null, true);

    (new SyncTravianAccountJob($account->id, null, false, true))->handle($syncAccountOverview);

    expect($account->fresh()->status)->toBe(AccountStatus::Syncing);
});
