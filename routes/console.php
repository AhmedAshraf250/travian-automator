<?php

use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Models\Account;
use App\Models\SystemSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('travian:sync-account {account}', function (int $account, SyncAccountOverview $syncAccountOverview): void {
    $accountModel = Account::query()->findOrFail($account);

    $this->info("Syncing account #{$accountModel->id} ({$accountModel->username})...");

    $syncAccountOverview->handle($accountModel);

    $this->info('Account overview synchronized successfully.');
})->purpose('Synchronize one Travian account overview using an isolated HTTP session.');

Schedule::command('travian:automation-cycle')
    ->when(fn (): bool => SystemSetting::automationEnabled())
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::command('travian:cleanup-runtime')
    ->hourly()
    ->withoutOverlapping(30);
