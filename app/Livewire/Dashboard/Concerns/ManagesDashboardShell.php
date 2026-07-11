<?php

namespace App\Livewire\Dashboard\Concerns;

trait ManagesDashboardShell
{
    /**
     * Keeps the activity log panel visible or hidden.
     */
    public bool $showActivityLog = true;

    /**
     * Stores the activity log drawer height as a viewport percentage.
     */
    public int $activityLogHeight = 22;

    /**
     * Stores which account rows are expanded in the UI.
     *
     * @var array<int, bool>
     */
    public array $expandedAccounts = [];

    /**
     * Toggle the account row expansion state.
     */
    public function toggleAccountExpansion(int $accountId): void
    {
        $currentState = $this->expandedAccounts[$accountId] ?? true;

        $this->expandedAccounts[$accountId] = ! $currentState;
    }

    /**
     * Return the account ids that should load and render village rows.
     *
     * @return list<int>
     */
    protected function expandedAccountIds(): array
    {
        return array_values(array_map(
            static fn (int|string $accountId): int => (int) $accountId,
            array_keys(array_filter($this->expandedAccounts, static fn (bool $isExpanded): bool => $isExpanded)),
        ));
    }

    /**
     * Toggle the activity log panel.
     */
    public function toggleActivityLog(): void
    {
        $this->showActivityLog = ! $this->showActivityLog;
    }

    /**
     * Increase the activity log drawer height.
     */
    public function increaseActivityLogHeight(): void
    {
        $this->activityLogHeight = min(36, $this->activityLogHeight + 4);
    }

    /**
     * Decrease the activity log drawer height.
     */
    public function decreaseActivityLogHeight(): void
    {
        $this->activityLogHeight = max(16, $this->activityLogHeight - 4);
    }
}
