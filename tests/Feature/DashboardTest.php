<?php

use App\Livewire\Dashboard\Index;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use App\Models\ImportDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('dashboard page loads successfully', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Travian Multi-Account Automation');
});

test('bulk import creates accounts and persists encrypted draft', function () {
    Livewire::test(Index::class)
        ->set('bulkImportDraft', '!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0')
        ->call('importAccounts');

    $account = Account::query()->first();

    expect($account)->not->toBeNull();
    expect($account?->username)->toBe('marshal');
    expect($account?->proxy_ip)->toBe('127.0.0.1');
    expect($account?->proxy_port)->toBe(8080);
    expect(AccountSetting::query()->count())->toBe(1);
    expect(ActivityLog::query()->count())->toBe(1);
    expect(ImportDraft::query()->where('key', 'bulk-account-import')->exists())->toBeTrue();
});

test('dashboard shows imported account username', function () {
    $account = Account::factory()->create([
        'username' => 'strategist',
    ]);

    $account->settings()->create([
        'resource_priorities' => [15, 11, 1, 1],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('strategist');
});
