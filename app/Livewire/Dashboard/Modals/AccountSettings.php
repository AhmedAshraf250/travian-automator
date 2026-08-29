<?php

namespace App\Livewire\Dashboard\Modals;

use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Livewire\Dashboard\Concerns\ManagesAccountSettings;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AccountSettings extends Component
{
    use ManagesAccountSettings {
        openAccountSettingsModal as protected loadAccountSettings;
        saveAccountSettings as protected persistAccountSettings;
    }

    #[On('dashboard-open-account-settings')]
    public function openAccountSettingsModal(int $accountId): void
    {
        $this->loadAccountSettings($accountId);
    }

    public function saveAccountSettings(): void
    {
        $this->persistAccountSettings();
        $this->dispatch('dashboard-row-updated');
    }

    public function render(): View
    {
        return view('livewire.dashboard.modals.account-settings', [
            'globalHeroDefaults' => $this->showAccountSettingsModal ? SystemSetting::heroDefaults() : [],
        ]);
    }

    protected function logManualActivity(Account $account, ?Village $village, string $message): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => null,
            'activity_type' => ActivityType::Manual,
            'status' => ActivityLogStatus::Done,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
