<?php

namespace App\Livewire\Dashboard\Modals;

use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Livewire\Dashboard\Concerns\ManagesVillageSettings;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class VillageSettings extends Component
{
    use ManagesVillageSettings {
        closeVillageSettingsModal as protected dismissVillageSettings;
        openVillageSettingsModal as protected loadVillageSettings;
        saveVillageSettings as protected persistVillageSettings;
    }

    public string $dashboardRevision = '';

    #[On('dashboard-open-village-settings')]
    public function openVillageSettingsModal(int $villageId): void
    {
        $this->loadVillageSettings($villageId);
        $this->dispatch('dashboard-modal-visibility-changed', open: true);
    }

    #[On('dashboard-open-village-troops')]
    public function openVillageTroopOrders(int $villageId): void
    {
        $this->loadVillageSettings($villageId);
        $this->villageSettingsTab = 'troops';
        $this->dispatch('dashboard-modal-visibility-changed', open: true);
    }

    public function closeVillageSettingsModal(): void
    {
        $this->dismissVillageSettings();
        $this->dispatch('dashboard-modal-visibility-changed', open: false);
    }

    public function saveVillageSettings(): void
    {
        $this->persistVillageSettings();
        $this->dispatch('dashboard-row-updated');
    }

    public function render(): View
    {
        $constructionDefaults = $this->showVillageBuildPlanModal
            ? SystemSetting::constructionDefaults()
            : [
                'field_priority' => SystemSetting::defaultFieldPriority(),
                'field_level_cap' => SystemSetting::defaultFieldLevelCap(),
            ];

        return view('livewire.dashboard.modals.village-settings', [
            'globalFieldPriority' => $constructionDefaults['field_priority'],
            'globalFieldLevelCap' => (int) $constructionDefaults['field_level_cap'],
        ]);
    }

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

    protected function computeDashboardRevision(): string
    {
        return (string) hrtime(true);
    }

    protected function secondsToWholeMinutes(int $seconds): int
    {
        return max(1, (int) ceil(max(60, $seconds) / 60));
    }

    protected function minutesToSeconds(int $minutes): int
    {
        return max(1, min(10080, $minutes)) * 60;
    }

    public function formatTradeDurationMinutes(int $minutes): string
    {
        $minutes = max(1, $minutes);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return "{$remainingMinutes}m";
        }

        if ($remainingMinutes <= 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }
}
