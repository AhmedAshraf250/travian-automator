<?php

namespace App\Livewire\Dashboard;

use App\Application\Accounts\Import\ImportBulkAccounts;
use App\Application\Accounts\Import\ImportDraftStore;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

/**
 * Renders the first automation dashboard with account import and activity visibility.
 */
class Index extends Component
{
    /**
     * Controls the bulk import modal visibility.
     */
    public bool $showImportModal = false;

    /**
     * Keeps the activity log panel visible or hidden.
     */
    public bool $showActivityLog = true;

    /**
     * Stores which account rows are expanded in the UI.
     *
     * @var array<int, bool>
     */
    public array $expandedAccounts = [];

    /**
     * Holds the textarea draft for bulk account import.
     */
    public string $bulkImportDraft = '';

    /**
     * Mount the dashboard component.
     */
    public function mount(ImportDraftStore $draftStore): void
    {
        $this->bulkImportDraft = $draftStore->get();
    }

    /**
     * Persist the draft whenever the textarea changes.
     */
    public function updatedBulkImportDraft(ImportDraftStore $draftStore): void
    {
        $draftStore->put($this->bulkImportDraft);
    }

    /**
     * Toggle the account row expansion state.
     */
    public function toggleAccountExpansion(int $accountId): void
    {
        $currentState = $this->expandedAccounts[$accountId] ?? false;

        $this->expandedAccounts[$accountId] = ! $currentState;
    }

    /**
     * Toggle the activity log panel.
     */
    public function toggleActivityLog(): void
    {
        $this->showActivityLog = ! $this->showActivityLog;
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

        session()->flash(
            'dashboard-banner',
            "Imported {$result['imported']} new account(s) and refreshed {$result['updated']} existing account(s).",
        );
    }

    /**
     * Activate an account from the dashboard.
     */
    public function activateAccount(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        $account->forceFill([
            'is_active' => true,
            'status' => AccountStatus::Active,
        ])->save();

        $this->logManualActivity($account, null, 'Account activated from dashboard.');
    }

    /**
     * Pause an account from the dashboard.
     */
    public function pauseAccount(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        $account->forceFill([
            'is_active' => false,
            'status' => AccountStatus::Paused,
        ])->save();

        $this->logManualActivity($account, null, 'Account paused from dashboard.');
    }

    /**
     * Queue a manual sync marker for an account.
     */
    public function requestAccountSync(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        $account->forceFill([
            'status' => AccountStatus::Syncing,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Pending,
            'message' => 'Sync requested and queued from dashboard.',
            'scheduled_at' => now(),
        ]);

        SyncTravianAccountJob::dispatch($account->id);

        session()->flash('dashboard-banner', "Account {$account->username} was queued for background sync.");
    }

    /**
     * Toggle village active state.
     */
    public function toggleVillage(int $villageId): void
    {
        $village = Village::query()->findOrFail($villageId);

        $village->forceFill([
            'is_active' => ! $village->is_active,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            $village->is_active ? 'Village activated from dashboard.' : 'Village paused from dashboard.',
        );
    }

    /**
     * Queue a manual village sync marker.
     */
    public function requestVillageSync(int $villageId): void
    {
        $village = Village::query()->with('account')->findOrFail($villageId);

        $village->account->forceFill([
            'status' => AccountStatus::Syncing,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $village->account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Pending,
            'message' => 'Village update requested and queued through the account overview sync.',
            'scheduled_at' => now(),
        ]);

        SyncTravianAccountJob::dispatch($village->account->id);

        session()->flash('dashboard-banner', "Village {$village->name} was queued for background sync.");
    }

    /**
     * Render the dashboard component.
     */
    public function render(): View
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('activity_logs')) {
            return view('livewire.dashboard.index', [
                ...$this->emptyDashboardState(),
            ]);
        }

        $accounts = $this->loadAccounts();
        $activityLogs = $this->loadActivityLogs();

        return view('livewire.dashboard.index', [
            'accounts' => $accounts,
            'activityLogs' => $activityLogs,
            'stats' => $this->buildStats($accounts),
        ]);
    }

    /**
     * Load the dashboard accounts with the relationships needed by the UI.
     *
     * @return Collection<int, Account>
     */
    protected function loadAccounts(): Collection
    {
        return Account::query()
            ->with([
                'settings',
                'villages.settings',
                'villages.resourceState',
                'villages.runtimeState',
                'villages.buildings' => fn ($query) => $query->orderBy('slot_id'),
            ])
            ->withCount('villages')
            ->orderByDesc('last_sync_at')
            ->latest('id')
            ->get();
    }

    /**
     * Load the most recent activity log rows for the footer timeline.
     *
     * @return Collection<int, ActivityLog>
     */
    protected function loadActivityLogs(): Collection
    {
        return ActivityLog::query()
            ->with(['account', 'village'])
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Build the top-level dashboard statistics.
     *
     * @param  Collection<int, Account>  $accounts
     * @return array<string, int>
     */
    protected function buildStats(Collection $accounts): array
    {
        return [
            'accounts' => $accounts->count(),
            'activeAccounts' => $accounts->where('is_active', true)->count(),
            'villages' => $accounts->sum('villages_count'),
            'syncing' => $accounts->where('status', AccountStatus::Syncing)->count(),
        ];
    }

    /**
     * Build the empty-state payload used before migrations are available.
     *
     * @return array{
     *     accounts: Collection<int, Account>,
     *     activityLogs: Collection<int, ActivityLog>,
     *     stats: array<string, int>
     * }
     */
    protected function emptyDashboardState(): array
    {
        return [
            'accounts' => collect(),
            'activityLogs' => collect(),
            'stats' => [
                'accounts' => 0,
                'activeAccounts' => 0,
                'villages' => 0,
                'syncing' => 0,
            ],
        ];
    }

    /**
     * Store a user-facing manual log entry.
     */
    protected function logManualActivity(Account $account, ?Village $village, string $message): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village?->id,
            'activity_type' => ActivityType::Manual,
            'status' => ActivityLogStatus::Done,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
