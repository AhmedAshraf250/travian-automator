@if ($showVillageBuildPlanModal)
    <div class="fixed inset-0 z-50 bg-stone-950/55 p-3 backdrop-blur-sm sm:p-4">
        <div
            class="mx-auto flex h-full max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] shadow-[0_30px_90px_rgba(0,0,0,0.28)]">
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-[var(--color-line)] px-5 py-4">
                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[var(--color-muted)]">
                            Village settings</p>
                        <span
                            class="rounded-full bg-[var(--color-panel-alt)] px-3 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                            {{ $editingVillageTribeLabel }}
                        </span>
                    </div>

                    <div>
                        <h2 class="truncate font-[var(--font-display)] text-2xl leading-none text-[var(--color-ink)]">
                            {{ $editingVillageName }}
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-[var(--color-muted)]">
                            Compact controls for automation switches, `dorf1` field priorities, and `dorf2` building
                            targets.
                        </p>
                    </div>
                </div>

                <button type="button" wire:click="closeVillageSettingsModal"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                <div class="space-y-4">
                    <section class="grid gap-3 lg:grid-cols-2">
                        <div class="rounded-[1.25rem] bg-[var(--color-panel-alt)] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-[var(--color-ink)]">Automation</h3>
                                    <p class="text-xs leading-5 text-[var(--color-muted)]">
                                        Enable or pause field and building execution for this village.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" wire:click="$toggle('villageFieldsAutomationDraft')"
                                    class="rounded-full border px-3 py-2 text-xs font-semibold transition {{ $villageFieldsAutomationDraft ? 'border-emerald-500/35 bg-emerald-500/10 text-emerald-800 hover:bg-emerald-500/15' : 'border-[var(--color-line-strong)] text-[var(--color-muted)] hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]' }}">
                                    Fields {{ $villageFieldsAutomationDraft ? 'ON' : 'OFF' }}
                                </button>

                                <button type="button" wire:click="$toggle('villageBuildingsAutomationDraft')"
                                    class="rounded-full border px-3 py-2 text-xs font-semibold transition {{ $villageBuildingsAutomationDraft ? 'border-sky-500/35 bg-sky-500/10 text-sky-800 hover:bg-sky-500/15' : 'border-[var(--color-line-strong)] text-[var(--color-muted)] hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]' }}">
                                    Buildings {{ $villageBuildingsAutomationDraft ? 'ON' : 'OFF' }}
                                </button>
                            </div>
                        </div>

                        <div class="rounded-[1.25rem] bg-[var(--color-panel-alt)] p-4">
                            <div>
                                <h3 class="font-semibold text-[var(--color-ink)]">Field priority</h3>
                                <p class="text-xs leading-5 text-[var(--color-muted)]">
                                    Lower rank means higher preference. Equal ranks are allowed, and the engine can
                                    fall back to another affordable field without letting lower priorities run too far ahead.
                                </p>
                            </div>

                            <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach (['wood' => 'Wood', 'clay' => 'Clay', 'iron' => 'Iron', 'crop' => 'Crop'] as $fieldKey => $fieldLabel)
                                    <label wire:key="field-priority-{{ $fieldKey }}" class="grid gap-1 text-sm">
                                        <span class="font-medium text-[var(--color-ink)]">{{ $fieldLabel }}</span>
                                        <select wire:model.live="villageFieldPriorityDraft.{{ $fieldKey }}"
                                            class="rounded-xl border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
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
                        </div>
                    </section>

                    <section class="space-y-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-semibold text-[var(--color-ink)]">Building targets</h3>
                                <p class="text-xs leading-5 text-[var(--color-muted)]">
                                    Target level `0` clears a slot plan. Occupied or fixed slots keep their valid gid.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-[var(--color-panel-alt)] px-3 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                Slots 19-40
                            </span>
                        </div>

                        <div class="grid gap-2 lg:grid-cols-2">
                            @foreach ($villageBuildingPlanDraft as $slotId => $draft)
                                @php
                                    $buildingOptions = $slotBuildingOptions[$slotId] ?? [];
                                    $slotIsFlexible = count($buildingOptions) > 1;
                                @endphp
                                <article wire:key="village-build-slot-{{ $slotId }}"
                                    class="rounded-[1.1rem] border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-3">
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span
                                                    class="rounded-full bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                                    Slot {{ $slotId }}
                                                </span>
                                                @if (!empty($draft['is_locked']))
                                                    <span
                                                        class="rounded-full border border-[var(--color-line-strong)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                                        Locked
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="mt-2 truncate text-sm font-semibold text-[var(--color-ink)]">
                                                {{ $draft['current_name'] }}
                                            </p>
                                            <p class="text-[11px] text-[var(--color-muted)]">
                                                Current level:
                                                {{ (int) ($draft['current_level'] ?? 0) > 0 ? $draft['current_level'] : '--' }}
                                            </p>
                                        </div>

                                        <label
                                            class="inline-flex items-center gap-2 rounded-full bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                            <input type="checkbox"
                                                wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.is_enabled"
                                                class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                            Enabled
                                        </label>
                                    </div>

                                    <div class="grid gap-2 sm:grid-cols-[1fr,5.25rem,5rem]">
                                        <label class="grid gap-1 text-sm">
                                            <span class="font-medium text-[var(--color-ink)]">Building</span>
                                            <select wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.building_gid"
                                                class="rounded-xl border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                                @if ($slotIsFlexible)
                                                    <option value="0">Choose</option>
                                                @endif
                                                @foreach ($buildingOptions as $option)
                                                    <option value="{{ $option['gid'] }}">{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                            @error("villageBuildingPlanDraft.$slotId.building_gid")
                                                <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                            @enderror
                                        </label>

                                        <label class="grid gap-1 text-sm">
                                            <span class="font-medium text-[var(--color-ink)]">Level</span>
                                            <input type="number" min="0" max="20"
                                                wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.target_level"
                                                class="rounded-xl border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]" />
                                            @error("villageBuildingPlanDraft.$slotId.target_level")
                                                <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                            @enderror
                                        </label>

                                        <label class="grid gap-1 text-sm">
                                            <span class="font-medium text-[var(--color-ink)]">Priority</span>
                                            <input type="number" min="1" max="999"
                                                wire:model.live="villageBuildingPlanDraft.{{ $slotId }}.priority"
                                                class="rounded-xl border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]" />
                                            @error("villageBuildingPlanDraft.$slotId.priority")
                                                <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                            @enderror
                                        </label>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>

            <div
                class="flex shrink-0 flex-col-reverse gap-3 border-t border-[var(--color-line)] bg-[var(--color-panel)] px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" wire:click="closeVillageSettingsModal"
                    class="inline-flex items-center justify-center rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Cancel
                </button>
                <button type="button" wire:click="saveVillageSettings"
                    class="inline-flex items-center justify-center rounded-full bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_16px_36px_rgba(176,93,31,0.28)] transition hover:brightness-110">
                    Save settings
                </button>
            </div>
        </div>
    </div>
@endif
