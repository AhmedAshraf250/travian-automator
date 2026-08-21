<?php

use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('runtime cleanup prunes old activity and failed job rows', function () {
    config()->set('travian.retention.activity_log_days', 7);
    config()->set('travian.retention.failed_job_days', 14);

    DB::table('activity_logs')->insert([
        'activity_type' => ActivityType::Sync->value,
        'status' => ActivityLogStatus::Done->value,
        'message' => 'old',
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ]);
    DB::table('activity_logs')->insert([
        'activity_type' => ActivityType::Sync->value,
        'status' => ActivityLogStatus::Done->value,
        'message' => 'new',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => 'old-failed-job',
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Failed',
        'failed_at' => now()->subDays(15),
    ]);
    DB::table('failed_jobs')->insert([
        'uuid' => 'new-failed-job',
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Failed',
        'failed_at' => now(),
    ]);

    $this->artisan('travian:cleanup-runtime')
        ->expectsOutput('Deleted 1 activity_logs row(s).')
        ->expectsOutput('Deleted 1 failed_jobs row(s).')
        ->assertSuccessful();

    expect(ActivityLog::query()->pluck('message')->all())->toBe(['new']);
    expect(DB::table('failed_jobs')->pluck('uuid')->all())->toBe(['new-failed-job']);
});

test('runtime cleanup enforces maximum retained activity rows', function () {
    config()->set('travian.retention.activity_log_days', 365);
    config()->set('travian.retention.activity_log_max_rows', 3);

    foreach (range(1, 5) as $index) {
        DB::table('activity_logs')->insert([
            'activity_type' => ActivityType::Sync->value,
            'status' => ActivityLogStatus::Done->value,
            'message' => "row-{$index}",
            'created_at' => now()->subMinutes(10 - $index),
            'updated_at' => now()->subMinutes(10 - $index),
        ]);
    }

    $this->artisan('travian:cleanup-runtime')
        ->assertSuccessful();

    expect(ActivityLog::query()->orderBy('id')->pluck('message')->all())->toBe([
        'row-3',
        'row-4',
        'row-5',
    ]);
});

test('runtime cleanup dry run leaves data untouched', function () {
    foreach (range(1, 5) as $index) {
        DB::table('activity_logs')->insert([
            'activity_type' => ActivityType::Sync->value,
            'status' => ActivityLogStatus::Done->value,
            'message' => "old-{$index}",
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);
    }

    $this->artisan('travian:cleanup-runtime --dry-run --batch-size=2 --max-batches=2')
        ->expectsOutput('Would delete 4 activity_logs row(s).')
        ->assertSuccessful();

    expect(ActivityLog::query()->count())->toBe(5);
});

test('runtime cleanup limits deletion work per run', function () {
    foreach (range(1, 5) as $index) {
        DB::table('activity_logs')->insert([
            'activity_type' => ActivityType::Sync->value,
            'status' => ActivityLogStatus::Done->value,
            'message' => "old-{$index}",
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);
    }

    $this->artisan('travian:cleanup-runtime --batch-size=2 --max-batches=2')
        ->expectsOutput('Deleted 4 activity_logs row(s).')
        ->assertSuccessful();

    expect(ActivityLog::query()->pluck('message')->all())->toBe(['old-5']);
});
