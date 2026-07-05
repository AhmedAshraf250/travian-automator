<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Application\Accounts\Import\ImportBulkAccounts;
use App\Application\Accounts\Import\ImportDraftStore;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Validation\ValidationException;
use Throwable;

trait ManagesAccountImports
{
    /**
     * Controls the bulk import modal visibility.
     */
    public bool $showImportModal = false;

    /**
     * Holds the textarea draft for bulk account import.
     */
    public string $bulkImportDraft = '';

    /**
     * Persist the draft whenever the textarea changes.
     */
    public function updatedBulkImportDraft(ImportDraftStore $draftStore): void
    {
        $draftStore->put($this->bulkImportDraft);
    }

    /**
     * Open the bulk import modal.
     */
    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    /**
     * Close the bulk import modal.
     */
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
    }

    /**
     * Parse and import accounts from the textarea content.
     */
    public function importAccounts(ImportBulkAccounts $importBulkAccounts, ImportDraftStore $draftStore): void
    {
        $this->validate([
            'bulkImportDraft' => ['required', 'string'],
        ]);

        try {
            $result = $importBulkAccounts->handle($this->bulkImportDraft);
        } catch (Throwable $throwable) {
            throw ValidationException::withMessages([
                'bulkImportDraft' => $throwable->getMessage(),
            ]);
        }

        $draftStore->put($this->bulkImportDraft);
        $this->showImportModal = false;

        $queuedLoginCount = $this->queueImportedAccountLogins($result['account_ids']);

        session()->flash(
            'dashboard-banner',
            "Accounts & Login updated {$result['imported']} new account(s), refreshed {$result['updated']}, archived {$result['archived']}, and queued {$queuedLoginCount} login/sync check(s).",
        );
    }

    /**
     * Queue login/sync checks for accounts touched by Accounts & Login.
     *
     * @param  list<int>  $accountIds
     */
    protected function queueImportedAccountLogins(array $accountIds): int
    {
        if ($accountIds === []) {
            return 0;
        }

        if (! SystemSetting::automationEnabled()) {
            return 0;
        }

        $queuedCount = 0;

        Account::query()
            ->whereKey($accountIds)
            ->where('is_archived', false)
            ->orderBy('id')
            ->get()
            ->each(function (Account $account) use (&$queuedCount): void {
                $account->forceFill([
                    'status' => AccountStatus::Syncing,
                    'connection_retry_after' => null,
                ])->save();

                ActivityLog::query()->create([
                    'account_id' => $account->id,
                    'activity_type' => ActivityType::Login,
                    'status' => ActivityLogStatus::Pending,
                    'message' => 'Login and account sync queued from Accounts & Login.',
                    'scheduled_at' => now(),
                ]);

                SyncTravianAccountJob::dispatch($account->id, null, true);
                $queuedCount++;
            });

        return $queuedCount;
    }
}
