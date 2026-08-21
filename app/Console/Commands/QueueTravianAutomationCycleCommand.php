<?php

namespace App\Console\Commands;

use App\Application\Accounts\Automation\DispatchDueAccountAutomation;
use App\Application\Accounts\Automation\RecoverStaleSyncingAccounts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('travian:automation-cycle {account? : Optional account id to queue regardless of next_automation_at} {--force : Queue all active accounts even when next_automation_at is in the future}')]
#[Description('Queue due Travian account automation jobs using the internal next-run planner.')]
class QueueTravianAutomationCycleCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(
        DispatchDueAccountAutomation $dispatchDueAccountAutomation,
        RecoverStaleSyncingAccounts $recoverStaleSyncingAccounts,
    ): int {
        $recoverStaleSyncingAccounts->handle();

        $accountId = $this->argument('account');
        $result = $dispatchDueAccountAutomation->handle(
            $accountId !== null ? (int) $accountId : null,
            (bool) $this->option('force'),
        );

        if ($result['disabled']) {
            $this->warn('Automation is disabled; no account automation jobs were queued.');

            return self::SUCCESS;
        }

        if ($result['queued'] === 0) {
            $this->info('No due accounts matched the requested automation cycle.');

            return self::SUCCESS;
        }

        $this->info("Queued {$result['queued']} smart automation job(s).");

        return self::SUCCESS;
    }
}
