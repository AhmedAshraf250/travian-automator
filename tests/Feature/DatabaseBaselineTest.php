<?php

use App\Models\Account;
use App\Models\AccountProxy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('sqlite baseline contains only the current application tables', function () {
    expect(config('database.default'))->toBe('sqlite')
        ->and(Schema::hasTable('accounts'))->toBeTrue()
        ->and(Schema::hasTable('villages'))->toBeTrue()
        ->and(Schema::hasTable('village_troop_snapshots'))->toBeTrue()
        ->and(Schema::hasTable('village_troop_orders'))->toBeTrue()
        ->and(Schema::hasTable('troop_queues'))->toBeFalse()
        ->and(Schema::hasTable('village_troop_plans'))->toBeFalse()
        ->and(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasTable('password_reset_tokens'))->toBeFalse()
        ->and(Schema::hasTable('job_batches'))->toBeFalse();
});

test('sqlite foreign keys remain valid across the account proxy cycle', function () {
    $account = Account::factory()->create();
    $proxy = AccountProxy::factory()->for($account)->create();

    $account->update(['active_account_proxy_id' => $proxy->id]);
    $proxy->delete();

    expect($account->fresh()->active_account_proxy_id)->toBeNull()
        ->and(DB::select('PRAGMA foreign_key_check'))->toBeEmpty();
});
