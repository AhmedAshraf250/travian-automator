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
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="border-b border-[var(--color-line)] px-5 pt-3">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ([
                        'generals' => 'Generals',
                        'layouts' => 'Layouts',
                        'troops' => 'Troops Training',
                        'celebrations' => 'Celebrations',
                        'trading' => 'Trading',
                    ] as $tabKey => $tabLabel)
                        <button type="button" wire:key="village-tab-{{ $tabKey }}"
                            wire:click="setVillageSettingsTab('{{ $tabKey }}')"
                            class="rounded-t-lg border px-3 py-2 text-sm font-semibold transition {{ $villageSettingsTab === $tabKey ? 'border-[var(--color-line)] border-b-[var(--color-panel)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-transparent bg-[var(--color-panel-alt)] text-[var(--color-muted)] hover:text-[var(--color-accent)]' }}">
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                @if ($villageSettingsTab === 'generals')
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.4fr)]">
                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Automation</h3>

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
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Field priority</h3>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]">
                                    <input type="checkbox" wire:model.live="villageInheritProgramPriorityDraft"
                                        class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                    Use program priority
                                </label>

                                <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]">
                                    <input type="checkbox" wire:model.live="villagePrioritizeCropFieldsWhenNegativeDraft"
                                        @disabled($villageInheritProgramPriorityDraft)
                                        class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-50 focus:ring-[var(--color-accent)]" />
                                    Prefer crop when negative
                                </label>
                            </div>

                            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach (['wood' => 'Wood', 'clay' => 'Clay', 'iron' => 'Iron', 'crop' => 'Crop'] as $fieldKey => $fieldLabel)
                                    <label wire:key="field-priority-{{ $fieldKey }}" class="grid gap-1 text-sm">
                                        <span class="font-medium text-[var(--color-ink)]">{{ $fieldLabel }}</span>
                                        <select wire:model.live="villageFieldPriorityDraft.{{ $fieldKey }}"
                                            @disabled($villageInheritProgramPriorityDraft)
                                            class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition disabled:opacity-50 focus:border-[var(--color-accent)]">
                                            @foreach ([1, 2, 3, 4] as $priorityValue)
                                                <option value="{{ $priorityValue }}">{{ $priorityValue }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach
                            </div>

                            @error('villageFieldPriorityDraft')
                                <p class="mt-3 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
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
                                    <input type="checkbox" wire:model.live="villageHeroResourcesDraft"
                                        class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                    Use Hero Resources
                                </label>
                            </div>
                        </section>
                    </div>
                @elseif ($villageSettingsTab === 'layouts')
                    <section class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Slots 19-40</h3>
                            <span class="rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                {{ count($villageBuildingPlanDraft) }} rows
                            </span>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)]">
                            <table class="min-w-[58rem] w-full border-separate border-spacing-0 text-left text-sm">
                                <thead class="bg-[var(--color-panel)] text-[11px] uppercase text-[var(--color-muted)]">
                                    <tr>
                                        <th class="border-b border-[var(--color-line)] px-3 py-2 font-semibold">Place ID</th>
                                        <th class="border-b border-[var(--color-line)] px-3 py-2 font-semibold">Current</th>
                                        <th class="border-b border-[var(--color-line)] px-3 py-2 font-semibold">Building</th>
                                        <th class="border-b border-[var(--color-line)] px-3 py-2 font-semibold">Max level</th>
                                        <th class="border-b border-[var(--color-line)] px-3 py-2 font-semibold">Priority</th>
                                        <th class="border-b border-[var(--color-line)] px-3 py-2 font-semibold">Active</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($villageBuildingPlanDraft as $slotId => $draft)
                                        @php
                                            $buildingOptions = $slotBuildingOptions[$slotId] ?? [];
                                            $slotIsFlexible = count($buildingOptions) > 1;
                                            $currentBuildingIcon = $buildingIconForGid((int) ($draft['current_gid'] ?? 0));
                                            $targetBuildingIcon = $buildingIconForGid((int) ($draft['building_gid'] ?? 0));
                                        @endphp
                                        <tr wire:key="village-build-slot-{{ $slotId }}" class="odd:bg-[var(--color-panel)]/60">
                                            <td class="border-b border-[var(--color-line)] px-3 py-2 font-mono text-xs font-semibold text-[var(--color-ink)]">
                                                {{ $slotId }}
                                            </td>
                                            <td class="border-b border-[var(--color-line)] px-3 py-2">
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
                                                        <p class="text-[11px] text-[var(--color-muted)]">
                                                            Lv {{ (int) ($draft['current_level'] ?? 0) > 0 ? $draft['current_level'] : '--' }}
                                                            @if (!empty($draft['is_locked']))
                                                                · locked
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="border-b border-[var(--color-line)] px-3 py-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)]">
                                                        @if ($targetBuildingIcon !== null)
                                                            <img src="{{ asset($targetBuildingIcon) }}" alt=""
                                                                class="max-h-7 max-w-7 object-contain"
                                                                onerror="this.parentElement.classList.add('opacity-40')" />
                                                        @else
                                                            <span class="text-[10px] font-semibold text-[var(--color-muted)]">--</span>
                                                        @endif
                                                    </span>
                                                    <select wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.building_gid"
                                                        class="min-w-0 flex-1 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                                        @if ($slotIsFlexible)
                                                            <option value="0">Empty</option>
                                                        @endif
                                                        @foreach ($buildingOptions as $option)
                                                            <option value="{{ $option['gid'] }}">{{ $option['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error("villageBuildingPlanDraft.$slotId.building_gid")
                                                    <span class="mt-1 block text-[11px] font-medium text-red-700">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="w-28 border-b border-[var(--color-line)] px-3 py-2">
                                                <input type="number" min="0" max="20"
                                                    wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.target_level"
                                                    class="w-full rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]" />
                                                @error("villageBuildingPlanDraft.$slotId.target_level")
                                                    <span class="mt-1 block text-[11px] font-medium text-red-700">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="w-28 border-b border-[var(--color-line)] px-3 py-2">
                                                <input type="number" min="1" max="999"
                                                    wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.priority"
                                                    class="w-full rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]" />
                                                @error("villageBuildingPlanDraft.$slotId.priority")
                                                    <span class="mt-1 block text-[11px] font-medium text-red-700">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="w-20 border-b border-[var(--color-line)] px-3 py-2">
                                                <label class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)]">
                                                    <input type="checkbox"
                                                        wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.is_enabled"
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
                    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                        <label class="inline-flex items-center gap-3 rounded-lg bg-[var(--color-panel)] px-4 py-3 text-sm font-semibold text-[var(--color-ink)]">
                            <input type="checkbox" wire:model.live="villageTroopTrainingEnabledDraft"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Enable troop training</span>
                        </label>
                    </section>
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
                                <select wire:model.live="villageCelebrationTypeDraft"
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
                                    wire:model.live="villageCelebrationMinimumCulturePointsDraft"
                                    @disabled(! $villageCelebrationEnabledDraft)
                                    class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition disabled:opacity-50 focus:border-[var(--color-accent)]" />
                                @error('villageCelebrationMinimumCulturePointsDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </section>
                @else
                    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Trading</h3>

                            <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Send Resources From this Village">
                                <input type="checkbox" wire:model.live="villageSendResourcesDraft"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                Allow sending resources
                            </label>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Send Resources From Other Villages when needed">
                                <input type="checkbox" wire:model.live="villageSupplyResourcesDraft"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                Supply this Village
                            </label>

                            <label class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="feed Troops with Crop">
                                <input type="checkbox" wire:model.live="villageSupplyNegativeCropDraft"
                                    @disabled(! $villageSupplyResourcesDraft)
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-50 focus:ring-[var(--color-accent)]" />
                                Supply Negative Crop
                            </label>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Minimum stock before sending</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="0" max="100"
                                        wire:model.live="villageSendMinResourcePercentageDraft"
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
                                        wire:model.live="villageSendReserveResourcePercentageDraft"
                                        @disabled(! $villageSendResourcesDraft)
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-[var(--color-ink)] outline-none disabled:opacity-50 focus:ring-0" />
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">%</span>
                                </div>
                                @error('villageSendReserveResourcePercentageDraft')
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
                    Cancel
                </button>
                <button type="button" wire:click="saveVillageSettings"
                    class="inline-flex items-center justify-center rounded-lg bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                    Save settings
                </button>
            </div>
        </div>
    </div>
@endif
