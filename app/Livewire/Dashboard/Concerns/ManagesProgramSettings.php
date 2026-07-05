<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

trait ManagesProgramSettings
{
    /**
     * Controls the program settings modal visibility.
     */
    public bool $showProgramSettingsModal = false;

    /**
     * Stores the active program settings modal tab.
     */
    public string $programSettingsTab = 'generals';

    /**
     * Stores the global fallback user agent used when an account has none.
     */
    public string $defaultUserAgent = '';

    /**
     * Stores the global hero automation defaults.
     *
     * @var array{
     *     adventures_enabled: bool,
     *     min_health: int,
     *     revive_enabled: bool,
     *     attribute_upgrade_enabled: bool,
     *     attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     * }
     */
    public array $globalHeroDefaultsDraft = [];

    /**
     * Stores the global editable field priority defaults.
     *
     * @var array{wood: int, clay: int, iron: int, crop: int}
     */
    public array $globalFieldPriorityDraft = [];

    /**
     * Stores whether global defaults should prefer crop fields during negative crop production.
     */
    public bool $globalPrioritizeCropFieldsWhenNegativeDraft = true;

    public int $globalFieldLevelCapDraft = 10;

    /**
     * @var array{max_duration_seconds: int}
     */
    public array $globalTradeDefaultsDraft = [];

    public int $globalTradeMaxDurationMinutesDraft = 300;

    /**
     * Open the program settings modal.
     */
    public function openProgramSettingsModal(): void
    {
        $this->defaultUserAgent = SystemSetting::defaultUserAgent() ?? '';
        $constructionDefaults = SystemSetting::constructionDefaults();
        $this->globalFieldPriorityDraft = $constructionDefaults['field_priority'];
        $this->globalPrioritizeCropFieldsWhenNegativeDraft = $constructionDefaults['prioritize_crop_fields_when_negative'];
        $this->globalFieldLevelCapDraft = (int) $constructionDefaults['field_level_cap'];
        $this->globalHeroDefaultsDraft = SystemSetting::heroDefaults();
        $this->globalTradeDefaultsDraft = SystemSetting::tradeDefaults();
        $this->globalTradeMaxDurationMinutesDraft = $this->secondsToWholeMinutes((int) ($this->globalTradeDefaultsDraft['max_duration_seconds'] ?? 18000));
        $this->programSettingsTab = 'generals';
        $this->showProgramSettingsModal = true;
    }

    /**
     * Close the program settings modal.
     */
    public function closeProgramSettingsModal(): void
    {
        $this->showProgramSettingsModal = false;
        $this->programSettingsTab = 'generals';
    }

    /**
     * Switch the program settings modal tab.
     */
    public function setProgramSettingsTab(string $tab): void
    {
        if (! in_array($tab, ['generals', 'hero', 'troops', 'merchants'], true)) {
            return;
        }

        $this->programSettingsTab = $tab;
    }

    /**
     * Toggle the global automation execution switch.
     */
    public function toggleAutomation(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $enabled = ! SystemSetting::automationEnabled();

        SystemSetting::setAutomationEnabled($enabled);

        session()->flash(
            'dashboard-banner',
            $enabled
                ? 'Global automation is now ON. Read and execution flows may continue.'
                : 'Global automation is now OFF. Queued Travian sync and execution flows will stop before sending external requests.',
        );
    }

    /**
     * Persist the global program settings.
     */
    public function saveProgramSettings(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $this->validate([
            'defaultUserAgent' => ['nullable', 'string', 'max:1000'],
            'globalFieldPriorityDraft.wood' => ['required', 'integer', 'min:1', 'max:4'],
            'globalFieldPriorityDraft.clay' => ['required', 'integer', 'min:1', 'max:4'],
            'globalFieldPriorityDraft.iron' => ['required', 'integer', 'min:1', 'max:4'],
            'globalFieldPriorityDraft.crop' => ['required', 'integer', 'min:1', 'max:4'],
            'globalPrioritizeCropFieldsWhenNegativeDraft' => ['boolean'],
            'globalFieldLevelCapDraft' => ['required', 'integer', 'min:1', 'max:20'],
            'globalTradeMaxDurationMinutesDraft' => ['required', 'integer', 'min:1', 'max:10080'],
            'globalHeroDefaultsDraft.adventures_enabled' => ['boolean'],
            'globalHeroDefaultsDraft.min_health' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.revive_enabled' => ['boolean'],
            'globalHeroDefaultsDraft.attribute_upgrade_enabled' => ['boolean'],
            'globalHeroDefaultsDraft.attribute_weights.power' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.attribute_weights.offBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.attribute_weights.defBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.attribute_weights.productionPoints' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        SystemSetting::setDefaultUserAgent($this->defaultUserAgent);
        SystemSetting::setConstructionDefaults([
            'field_priority' => $this->globalFieldPriorityDraft,
            'prioritize_crop_fields_when_negative' => $this->globalPrioritizeCropFieldsWhenNegativeDraft,
            'field_level_cap' => $this->globalFieldLevelCapDraft,
        ]);
        SystemSetting::setTradeDefaults([
            'max_duration_seconds' => $this->minutesToSeconds($this->globalTradeMaxDurationMinutesDraft),
        ]);
        SystemSetting::setHeroDefaults($this->globalHeroDefaultsDraft);
        $this->showProgramSettingsModal = false;
        $this->programSettingsTab = 'generals';

        session()->flash(
            'dashboard-banner',
            trim($this->defaultUserAgent) !== ''
                ? 'Program settings saved. Accounts without a custom user agent will now inherit the global fallback user agent.'
                : 'Program settings saved. Global construction defaults were also updated.',
        );
    }
}
