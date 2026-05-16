<?php

use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('travian:sync-account {account}', function (int $account, SyncAccountOverview $syncAccountOverview): void {
    $accountModel = Account::query()->findOrFail($account);

    $this->info("Syncing account #{$accountModel->id} ({$accountModel->username})...");

    $syncAccountOverview->handle($accountModel);

    $this->info('Account overview synchronized successfully.');
})->purpose('Synchronize one Travian account overview using an isolated HTTP session.');

Artisan::command('travian:automation-cycle {account?}', function (?int $account = null): void {
    $query = Account::query()
        ->where('is_active', true)
        ->where('is_archived', false);

    if ($account !== null) {
        $query->whereKey($account);
    }

    $accounts = $query->orderBy('id')->get();

    if ($accounts->isEmpty()) {
        $this->warn('No active accounts matched the requested automation cycle.');

        return;
    }

    foreach ($accounts as $accountModel) {
        SyncTravianAccountJob::dispatch($accountModel->id);
        $this->info("Queued sync + automation cycle for account #{$accountModel->id} ({$accountModel->username}).");
    }
})->purpose('Queue a full sync plus construction automation cycle for one account or all active accounts.');
