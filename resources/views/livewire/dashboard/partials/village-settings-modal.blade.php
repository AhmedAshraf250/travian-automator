@php
    $buildingIconForGid = static function (int $gid): ?string {
        if ($gid < 1) {
            return null;
        }

        $candidates = [
            "assets/buildings-icons/type{$gid}_small.png",
            "assets/buildings-icons/type{$gid}_teahouse_small.png",
        ];

        foreach ($candidates as $candidate) {
            if (file_exists(public_path($candidate))) {
                return $candidate;
            }
        }

        return null;
    };
@endphp

@if ($showVillageBuildPlanModal)
    <div class="fixed inset-0 z-50 bg-slate-950/55 p-3 backdrop-blur-sm sm:p-4">
        <div class="mx-auto flex h-full max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-2xl">
            <div class="flex shrink-0 items-center justify-between gap-4 border-b border-[var(--color-line)] px-5 py-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Village settings</p>
                    </div>
                    <h2 class="mt-1 truncate font-[var(--font-display)] text-2xl font-semibold text-[var(--color-ink)]">
                        {{ $editingVillageName }}
                    </h2>
                </div>

                <button type="button" wire:click="closeVillageSettingsModal"
                    aria-label="Close village settings" title="Close village settings"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="border-b border-[var(--color-line)] bg-[var(--color-panel-alt)] px-5 py-2.5">
                <div role="tablist" aria-label="Village settings sections" class="inline-flex flex-wrap gap-1 rounded-xl border border-[var(--color-line)] bg-[var(--color-panel)] p-1 shadow-sm">
                    @foreach ([
                        'generals' => 'General',
                        'layouts' => 'Layouts',
                        'troops' => 'Troop Training',
                        'celebrations' => 'Celebrations',
                        'trading' => 'Trading',
                    ] as $tabKey => $tabLabel)
                        <button type="button" wire:key="village-tab-{{ $tabKey }}"
                            wire:click="setVillageSettingsTab('{{ $tabKey }}')"
                            role="tab" aria-selected="{{ $villageSettingsTab === $tabKey ? 'true' : 'false' }}"
                            class="cursor-pointer rounded-lg border px-3.5 py-2 text-sm font-semibold transition {{ $villageSettingsTab === $tabKey ? 'border-[var(--color-accent)] bg-[var(--color-accent)] text-[var(--color-accent-contrast)] shadow-sm' : 'border-transparent text-[var(--color-muted)] hover:border-[var(--color-line-strong)] hover:bg-[var(--color-panel-alt)] hover:text-[var(--color-ink)]' }}">
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="min-h-0 flex-1 px-5 py-4 {{ $villageSettingsTab === 'layouts' ? 'overflow-hidden' : 'overflow-y-auto' }}">
                @if ($villageSettingsTab === 'generals')
                    @php
                        $priorityLabels = ['wood' => 'Wood', 'clay' => 'Clay', 'iron' => 'Iron', 'crop' => 'Crop'];
                        $effectivePriorityDraft = $villageInheritProgramPriorityDraft
                            ? ($globalFieldPriority ?? \App\Models\VillageSetting::defaultFieldPriority())
                            : $villageFieldPriorityDraft;
                        $effectiveFieldLevelCapDraft = $villageFieldLevelCapModeDraft === 'custom'
                            ? (int) $villageFieldLevelCapDraft
                            : (int) ($globalFieldLevelCap ?? \App\Models\VillageSetting::defaultFieldLevelCap());
                        $effectiveFieldLevelCapDraft = max(1, min($editingVillageIsCapital ? 20 : 10, $effectiveFieldLevelCapDraft));
                    @endphp
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.4fr)]">
                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Automatic upgrades</h3>
                                    <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">
                                        These switches match the F and B controls on the village row.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" wire:click="$toggle('villageFieldsAutomationDraft')"
                                    class="inline-flex items-center gap-2 rounded-full border px-2 py-1.5 text-xs font-semibold transition {{ $villageFieldsAutomationDraft ? 'border-emerald-600/35 bg-emerald-500/10 text-emerald-800' : 'border-rose-600/30 bg-rose-500/10 text-rose-800' }}"
                                    title="Enable or pause all field upgrades">
                                    <span class="relative inline-flex h-6 w-11 items-center rounded-full {{ $villageFieldsAutomationDraft ? 'bg-emerald-500' : 'bg-rose-500' }}">
                                        <span class="absolute inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-[10px] font-black shadow-sm transition {{ $villageFieldsAutomationDraft ? 'right-0.5 text-emerald-700' : 'left-0.5 text-rose-700' }}">
                                            {{ $villageFieldsAutomationDraft ? '✓' : '×' }}
                                        </span>
                                    </span>
                                    Fields
                                </button>

                                <button type="button" wire:click="$toggle('villageBuildingsAutomationDraft')"
                                    class="inline-flex items-center gap-2 rounded-full border px-2 py-1.5 text-xs font-semibold transition {{ $villageBuildingsAutomationDraft ? 'border-emerald-600/35 bg-emerald-500/10 text-emerald-800' : 'border-rose-600/30 bg-rose-500/10 text-rose-800' }}"
                                    title="Enable or pause all building upgrades">
                                    <span class="relative inline-flex h-6 w-11 items-center rounded-full {{ $villageBuildingsAutomationDraft ? 'bg-emerald-500' : 'bg-rose-500' }}">
                                        <span class="absolute inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-[10px] font-black shadow-sm transition {{ $villageBuildingsAutomationDraft ? 'right-0.5 text-emerald-700' : 'left-0.5 text-rose-700' }}">
                                            {{ $villageBuildingsAutomationDraft ? '✓' : '×' }}
                                        </span>
                                    </span>
                                    Buildings
                                </button>
                            </div>
                        </section>

                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Field priority mode</h3>
                                    <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">
                                        Inherited priority follows Program settings; custom priority applies only to this village.
                                    </p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $villageInheritProgramPriorityDraft ? 'bg-sky-500/10 text-sky-800' : 'bg-emerald-500/10 text-emerald-800' }}">
                                    {{ $villageInheritProgramPriorityDraft ? 'Inherited' : 'Custom' }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" wire:click="$set('villageInheritProgramPriorityDraft', true)"
                                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $villageInheritProgramPriorityDraft ? 'border-[var(--color-accent)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-[var(--color-line)] bg-[var(--color-panel)] text-[var(--color-muted)] hover:border-[var(--color-accent)]' }}">
                                    Use Program priority
                                </button>

                                <button type="button" wire:click="$set('villageInheritProgramPriorityDraft', false)"
                                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ ! $villageInheritProgramPriorityDraft ? 'border-[var(--color-accent)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-[var(--color-line)] bg-[var(--color-panel)] text-[var(--color-muted)] hover:border-[var(--color-accent)]' }}">
                                    Custom village priority
                                </button>

                                <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]">
                                    <input type="checkbox" wire:model.change="villagePrioritizeCropFieldsWhenNegativeDraft"
                                        @disabled($villageInheritProgramPriorityDraft)
                                        class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-50 focus:ring-[var(--color-accent)]" />
                                    Prefer crop when negative
                                </label>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($priorityLabels as $fieldKey => $fieldLabel)
                                    <span wire:key="effective-field-priority-{{ $fieldKey }}"
                                        class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                        {{ $fieldLabel }} priority {{ (int) ($effectivePriorityDraft[$fieldKey] ?? 0) }}
                                    </span>
                                @endforeach
                            </div>

                            @unless ($villageInheritProgramPriorityDraft)
                                <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                    @foreach ($priorityLabels as $fieldKey => $fieldLabel)
                                        <label wire:key="field-priority-{{ $fieldKey }}" class="grid gap-1 text-sm">
                                            <span class="font-medium text-[var(--color-ink)]">{{ $fieldLabel }}</span>
                                            <select wire:model.change="villageFieldPriorityDraft.{{ $fieldKey }}"
                                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                                @foreach ([1, 2, 3, 4] as $priorityValue)
                                                    <option value="{{ $priorityValue }}">{{ $priorityValue }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endforeach
                                </div>
                            @endunless

                            @error('villageFieldPriorityDraft')
                                <p class="mt-3 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </section>

                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4 lg:col-span-2">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Field level limit</h3>
                                    <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">
                                        Controls the highest resource field level automation may target. Non-capital villages are always capped at level 10.
                                    </p>
                                    <span class="mt-2 inline-flex rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                        Effective limit: level {{ $effectiveFieldLevelCapDraft }}
                                    </span>
                                </div>

                                <label class="grid min-w-44 gap-1 text-sm">
                                    <span class="font-medium text-[var(--color-ink)]">Mode</span>
                                    <select wire:model.live="villageFieldLevelCapModeDraft"
                                        class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                        <option value="inherit">Use program default</option>
                                        <option value="custom">Custom limit</option>
                                    </select>
                                </label>

                                <label class="grid w-36 gap-1 text-sm">
                                    <span class="font-medium text-[var(--color-ink)]">Max level</span>
                                    <input type="number" min="1" max="{{ $editingVillageIsCapital ? 20 : 10 }}"
                                        wire:model.blur="villageFieldLevelCapDraft"
                                        @disabled($villageFieldLevelCapModeDraft !== 'custom')
                                        class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition disabled:opacity-50 focus:border-[var(--color-accent)]">
                                </label>
                            </div>
                        </section>

                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4 lg:col-span-2">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Hero resources</h3>
                                    <p class="mt-1 max-w-2xl text-xs leading-5 text-[var(--color-muted)]">
                                        Use stored hero resource rewards to cover construction shortages before trying marketplace support.
                                    </p>
                                </div>

                                <label class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                    title="Use hero inventory resources before marketplace support">
                                    <input type="checkbox" wire:model.change="villageHeroResourcesDraft"
                                        class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                    Use Hero Resources
                                </label>
                            </div>
                        </section>
                    </div>
                @elseif ($villageSettingsTab === 'layouts')
                    <section class="flex h-full min-h-0 flex-col gap-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Village building positions</h3>
                        </div>

                        <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-2">
                            <table class="min-w-[58rem] w-full border-separate border-spacing-y-2 text-left text-sm">
                                <thead class="text-[11px] uppercase text-[var(--color-muted)] shadow-sm">
                                    <tr>
                                        <th class="sticky top-0 z-30 bg-[var(--color-panel)] px-3 py-2 font-semibold">Position</th>
                                        <th class="sticky top-0 z-30 bg-[var(--color-panel)] px-3 py-2 font-semibold">Current</th>
                                        <th class="sticky top-0 z-30 bg-[var(--color-panel)] px-3 py-2 font-semibold">Building</th>
                                        <th class="sticky top-0 z-30 bg-[var(--color-panel)] px-3 py-2 font-semibold">Max level</th>
                                        <th class="sticky top-0 z-30 bg-[var(--color-panel)] px-3 py-2 font-semibold">Priority</th>
                                        <th class="sticky top-0 z-30 bg-[var(--color-panel)] px-3 py-2 font-semibold">Active</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($villageBuildingPlanDraft as $slotId => $draft)
                                        @php
                                            $buildingOptions = $slotBuildingOptions[$slotId] ?? [];
                                            $slotIsFlexible = count($buildingOptions) > 1;
                                            $slotIsFixed = in_array((int) $slotId, [26, 39, 40], true);
                                            $slotHasBuilding = (int) ($draft['current_gid'] ?? 0) > 0;
                                            $buildingIsReadonly = $slotHasBuilding || $slotIsFixed || ! $slotIsFlexible;
                                            $displayBuildingOption = collect($buildingOptions)->firstWhere('gid', (int) ($draft['building_gid'] ?? 0));
                                            $displayBuildingName = $displayBuildingOption['label'] ?? ($draft['current_name'] ?? 'Empty');
                                            $currentBuildingIcon = $buildingIconForGid((int) ($draft['current_gid'] ?? 0));
                                            $currentIsMaxed = (bool) ($draft['current_is_maxed'] ?? false);
                                            $currentMaxLevel = $draft['current_max_level'] ?? null;
                                            $rowStateClasses = $slotIsFixed
                                                ? 'bg-slate-200/90 ring-slate-400/70'
                                                : ($slotHasBuilding
                                                    ? 'bg-emerald-50/80 ring-emerald-500/25'
                                                    : 'bg-[var(--color-panel)] ring-[var(--color-line)]');
                                        @endphp
                                        <tr wire:key="village-build-slot-{{ $slotId }}"
                                            class="relative shadow-sm ring-1 transition focus-within:z-20 focus-within:ring-[var(--color-accent)] {{ $rowStateClasses }}">
                                            <td class="rounded-l-lg px-3 py-2 font-mono text-xs font-semibold text-[var(--color-ink)] {{ $slotIsFixed ? 'border-l-4 border-slate-500' : ($slotHasBuilding ? 'border-l-4 border-emerald-500' : '') }}">
                                                {{ $slotId }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex max-w-[13rem] items-center gap-2">
                                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-[var(--color-line)] bg-[var(--color-panel)]">
                                                        @if ($currentBuildingIcon !== null)
                                                            <img src="{{ asset($currentBuildingIcon) }}" alt=""
                                                                class="max-h-6 max-w-6 object-contain"
                                                                onerror="this.parentElement.classList.add('opacity-40')" />
                                                        @else
                                                            <span class="text-[10px] font-semibold text-[var(--color-muted)]">--</span>
                                                        @endif
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-xs font-semibold text-[var(--color-ink)]">{{ $draft['current_name'] }}</p>
                                                        <div class="mt-1 flex flex-wrap items-center gap-1">
                                                            @if ((int) ($draft['current_level'] ?? 0) > 0)
                                                                <span class="inline-flex rounded-md border border-emerald-600/25 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-bold text-emerald-800">
                                                                    Lv {{ $draft['current_level'] }}
                                                                </span>
                                                            @else
                                                                <span class="inline-flex rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-0.5 text-[11px] font-semibold text-[var(--color-muted)]">
                                                                    Lv --
                                                                </span>
                                                            @endif
                                                            @if ($currentIsMaxed)
                                                                <span title="Reached final level{{ $currentMaxLevel !== null ? ' '.$currentMaxLevel : '' }}"
                                                                    class="inline-flex rounded-md border border-amber-500/25 bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                                                                    max
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex items-center gap-2">
                                                    @if ($buildingIsReadonly)
                                                        <div
                                                            class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm font-semibold text-[var(--color-ink)] {{ $slotIsFixed ? 'border-slate-300 bg-slate-100' : 'border-[var(--color-line)] bg-white/70' }}">
                                                            {{ (int) ($draft['building_gid'] ?? 0) > 0 ? $displayBuildingName : 'Empty' }}
                                                        </div>
                                                    @else
                                                        <div class="relative min-w-0 flex-1"
                                                            x-data="{ open: false }"
                                                            @keydown.escape.window="open = false">
                                                            <button type="button"
                                                                @click="open = !open"
                                                                class="flex min-h-10 w-full min-w-0 items-center justify-between gap-2 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-left text-sm text-[var(--color-ink)] outline-none transition hover:border-[var(--color-accent)] focus:border-[var(--color-accent)]">
                                                                <span class="inline-flex min-w-0 items-center gap-2">
                                                                    @if ((int) ($draft['building_gid'] ?? 0) > 0 && ($displayBuildingOption['icon'] ?? null) !== null)
                                                                        <img src="{{ asset($displayBuildingOption['icon']) }}" alt=""
                                                                            class="h-5 w-5 shrink-0 object-contain"
                                                                            onerror="this.classList.add('hidden')" />
                                                                    @else
                                                                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border border-[var(--color-line)] bg-[var(--color-panel-alt)] text-[10px] font-semibold text-[var(--color-muted)]">--</span>
                                                                    @endif
                                                                    <span class="truncate">{{ (int) ($draft['building_gid'] ?? 0) > 0 ? $displayBuildingName : 'Empty' }}</span>
                                                                </span>
                                                                <span class="shrink-0 text-xs text-[var(--color-muted)]">▾</span>
                                                            </button>

                                                            <div x-cloak x-show="open" @click.outside="open = false"
                                                                class="absolute left-0 right-0 top-full z-50 mt-1 max-h-72 overflow-y-auto rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-1 shadow-[0_18px_55px_rgba(15,23,42,0.18)]">
                                                                <button type="button"
                                                                    @click="$wire.set('villageBuildingPlanDraft.{{ $slotId }}.building_gid', 0); $wire.set('villageBuildingPlanDraft.{{ $slotId }}.target_level', 0); open = false"
                                                                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm font-semibold text-[var(--color-ink)] hover:bg-[var(--color-panel-alt)]">
                                                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-[var(--color-line)] bg-[var(--color-panel-alt)] text-[10px] text-[var(--color-muted)]">--</span>
                                                                    <span class="truncate">Empty</span>
                                                                </button>
                                                                @foreach ($buildingOptions as $option)
                                                                    @php
                                                                        $optionSelectable = (bool) ($option['selectable'] ?? true);
                                                                        $optionReason = $option['unavailable_reason'] ?? null;
                                                                    @endphp
                                                                    <button type="button"
                                                                        wire:key="slot-{{ $slotId }}-building-option-{{ $option['gid'] }}"
                                                                        @if ($optionSelectable)
                                                                            @click="$wire.set('villageBuildingPlanDraft.{{ $slotId }}.building_gid', {{ (int) $option['gid'] }}); $wire.set('villageBuildingPlanDraft.{{ $slotId }}.target_level', 1); open = false"
                                                                        @endif
                                                                        @disabled(! $optionSelectable)
                                                                        title="{{ $optionReason ?? $option['label'] }}"
                                                                        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm font-semibold transition {{ $optionSelectable ? 'text-[var(--color-ink)] hover:bg-[var(--color-panel-alt)]' : 'cursor-not-allowed bg-rose-50/80 text-rose-900 opacity-80' }}">
                                                                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded bg-white ring-1 {{ $optionSelectable ? 'ring-black/5' : 'ring-rose-200' }}">
                                                                            @if (($option['icon'] ?? null) !== null)
                                                                                <img src="{{ asset($option['icon']) }}" alt=""
                                                                                    class="h-5 w-5 object-contain {{ $optionSelectable ? '' : 'opacity-60' }}"
                                                                                    onerror="this.classList.add('hidden')" />
                                                                            @else
                                                                                <span class="text-xs text-[var(--color-muted)]">◇</span>
                                                                            @endif
                                                                        </span>
                                                                        <span class="min-w-0 flex-1"><span class="block truncate">{{ $option['label'] }}</span>@if (! $optionSelectable && $optionReason)<span class="mt-0.5 block text-[10px] font-medium leading-4 text-rose-700">{{ $optionReason }}</span>@endif</span>
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error("villageBuildingPlanDraft.$slotId.building_gid")
                                                    <span class="mt-1 block text-[11px] font-medium text-red-700">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="w-28 px-3 py-2">
                                                <input type="number" min="0" max="20"
                                                    wire:model.blur="villageBuildingPlanDraft.{{ $slotId }}.target_level"
                                                    class="w-full rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]" />
                                                @error("villageBuildingPlanDraft.$slotId.target_level")
                                                    <span class="mt-1 block text-[11px] font-medium text-red-700">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="w-28 px-3 py-2">
                                                <input type="number" min="1" max="4"
                                                    wire:model.blur="villageBuildingPlanDraft.{{ $slotId }}.priority"
                                                    class="w-full rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]" />
                                                @error("villageBuildingPlanDraft.$slotId.priority")
                                                    <span class="mt-1 block text-[11px] font-medium text-red-700">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="w-20 rounded-r-lg px-3 py-2">
                                                <label class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)]">
                                                    <input type="checkbox"
                                                        wire:model.change="villageBuildingPlanDraft.{{ $slotId }}.is_enabled"
                                                        class="h-4 w-4 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @elseif ($villageSettingsTab === 'troops')
                    <livewire:dashboard.village.troop-orders :village-id="$editingVillageId"
                        :key="'village-troop-orders-'.$editingVillageId" />
                @elseif ($villageSettingsTab === 'celebrations')
                    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Celebrations</h3>

                            <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]">
                                <input type="checkbox" wire:model.live="villageCelebrationEnabledDraft"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                Enable celebrations
                            </label>
                        </div>

                        @if ($villageCelebrationReadinessMessage !== '')
                            <p class="mt-3 rounded-lg border border-red-500/25 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-700">
                                {{ $villageCelebrationReadinessMessage }}
                            </p>
                        @endif

                        @error('villageCelebrationEnabledDraft')
                            <p class="mt-3 text-xs font-medium text-red-700">{{ $message }}</p>
                        @enderror

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Preferred type</span>
                                <select wire:model.change="villageCelebrationTypeDraft"
                                    @disabled(! $villageCelebrationEnabledDraft)
                                    class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition disabled:opacity-50 focus:border-[var(--color-accent)]">
                                    <option value="small">Small first</option>
                                    <option value="great">Great first</option>
                                </select>
                                @error('villageCelebrationTypeDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Minimum culture points</span>
                                <input type="number" min="0" max="2000"
                                    wire:model.blur="villageCelebrationMinimumCulturePointsDraft"
                                    @disabled(! $villageCelebrationEnabledDraft)
                                    class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition disabled:opacity-50 focus:border-[var(--color-accent)]" />
                                @error('villageCelebrationMinimumCulturePointsDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="inline-flex items-start gap-2 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] px-3 py-2 text-xs md:col-span-2">
                                <input type="checkbox" wire:model.change="villageCelebrationUseHeroResourcesDraft"
                                    @disabled(! $villageCelebrationEnabledDraft)
                                    class="mt-0.5 h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-50 focus:ring-[var(--color-accent)]" />
                                <span><strong class="block text-[var(--color-ink)]">Use Hero resources when a celebration is short</strong><span class="mt-0.5 block leading-4 text-[var(--color-muted)]">Use only the missing amount from the Hero inventory before starting the celebration.</span></span>
                            </label>
                        </div>
                    </section>
                @else
                    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                        <div class="flex flex-col gap-1">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-[var(--color-ink)]">Trading policy</h3>
                                <p class="mt-1 max-w-2xl text-xs leading-5 text-[var(--color-muted)]">
                                    Saved limits control automatic support. Use TR for a one-time transfer.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-2 sm:grid-cols-3">
                            <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Allow this village to send surplus resources to other villages.">
                                <input type="checkbox" wire:model.live="villageSendResourcesDraft"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                Send resources
                            </label>

                            <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Allow other villages to supply this village when it needs resources.">
                                <input type="checkbox" wire:model.live="villageSupplyResourcesDraft"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                Receive support
                            </label>

                            <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Allow crop support when this village has negative crop production.">
                                <input type="checkbox" wire:model.live="villageSupplyNegativeCropDraft"
                                    @disabled(! $villageSupplyResourcesDraft)
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-50 focus:ring-[var(--color-accent)]" />
                                Negative crop support
                            </label>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Minimum stock before sending</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="0" max="100"
                                        wire:model.blur="villageSendMinResourcePercentageDraft"
                                        @disabled(! $villageSendResourcesDraft)
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-[var(--color-ink)] outline-none disabled:opacity-50 focus:ring-0" />
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">%</span>
                                </div>
                                @error('villageSendMinResourcePercentageDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Reserve after sending</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="0" max="100"
                                        wire:model.blur="villageSendReserveResourcePercentageDraft"
                                        @disabled(! $villageSendResourcesDraft)
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-[var(--color-ink)] outline-none disabled:opacity-50 focus:ring-0" />
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">%</span>
                                </div>
                                @error('villageSendReserveResourcePercentageDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="grid gap-1 text-sm md:col-span-2">
                                <span class="font-medium text-[var(--color-ink)]">Max one-way merchant travel time</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="1" max="10080"
                                        wire:model.live.debounce.900ms="villageTradeMaxDurationMinutesDraft"
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-[var(--color-ink)] outline-none focus:ring-0" />
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">minutes</span>
                                </div>
                                <span class="text-[11px] leading-4 text-[var(--color-muted)]">
                                    {{ (int) $villageTradeMaxDurationMinutesDraft }} minutes = {{ $this->formatTradeDurationMinutes((int) $villageTradeMaxDurationMinutesDraft) }}. Longer transfers will not be sent.
                                </span>
                                @error('villageTradeMaxDurationMinutesDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </section>
                @endif
            </div>

            <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-[var(--color-line)] bg-[var(--color-panel)] px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" wire:click="closeVillageSettingsModal"
                    class="inline-flex items-center justify-center rounded-lg border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    {{ $villageSettingsTab === 'troops' ? 'Close' : 'Cancel' }}
                </button>
                @if ($villageSettingsTab !== 'troops')
                    <button type="button" wire:click="saveVillageSettings"
                        class="inline-flex items-center justify-center rounded-lg bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                        Save settings
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
