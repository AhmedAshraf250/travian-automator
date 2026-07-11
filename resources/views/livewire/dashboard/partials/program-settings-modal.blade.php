@if ($showProgramSettingsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-[var(--color-line)] px-5 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Program settings</p>
                    <h2 class="mt-1 font-[var(--font-display)] text-2xl font-semibold">Shared defaults</h2>
                </div>

                <button type="button" wire:click="closeProgramSettingsModal"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="border-b border-[var(--color-line)] px-5 pt-3">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ([
                        'generals' => 'Generals',
                        'hero' => 'Hero',
                        'troops' => 'Troops Training',
                        'merchants' => 'Trading',
                    ] as $tabKey => $tabLabel)
                        <button type="button" wire:key="program-tab-{{ $tabKey }}"
                            wire:click="setProgramSettingsTab('{{ $tabKey }}')"
                            class="rounded-t-lg border px-3 py-2 text-sm font-semibold transition {{ $programSettingsTab === $tabKey ? 'border-[var(--color-line)] border-b-[var(--color-panel)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-transparent bg-[var(--color-panel-alt)] text-[var(--color-muted)] hover:text-[var(--color-accent)]' }}">
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                @if ($programSettingsTab === 'generals')
                    <div class="space-y-4">
                        <label class="block space-y-2">
                            <span class="text-sm font-semibold">Global fallback user agent</span>
                            <textarea wire:model="defaultUserAgent" rows="4"
                                class="w-full rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-3 text-sm leading-6 text-[var(--color-ink)] outline-none transition placeholder:text-[var(--color-muted)] focus:border-[var(--color-accent)] focus:ring-4 focus:ring-[color:var(--color-accent-soft)]"
                                placeholder="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ..."></textarea>
                        </label>

                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Field priority</h3>

                            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach (['wood' => 'Wood', 'clay' => 'Clay', 'iron' => 'Iron', 'crop' => 'Crop'] as $fieldKey => $fieldLabel)
                                    <label wire:key="global-field-priority-{{ $fieldKey }}" class="grid gap-1 text-sm">
                                        <span class="font-medium text-[var(--color-ink)]">{{ $fieldLabel }}</span>
                                        <select wire:model.change="globalFieldPriorityDraft.{{ $fieldKey }}"
                                            class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                            @foreach ([1, 2, 3, 4] as $priorityValue)
                                                <option value="{{ $priorityValue }}">{{ $priorityValue }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach
                            </div>

                            <label class="mt-4 flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-sm font-semibold">
                                <input type="checkbox" wire:model.change="globalPrioritizeCropFieldsWhenNegativeDraft"
                                    class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                <span>Prefer crop fields while crop production is negative</span>
                            </label>
                        </div>

                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem] md:items-end">
                                <div>
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Field max level</h3>
                                    <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">
                                        Global ceiling for resource field upgrades. Non-capital villages are always capped at level 10 by Travian rules.
                                    </p>
                                </div>

                                <label class="grid gap-1 text-sm">
                                    <span class="font-medium text-[var(--color-ink)]">Max level</span>
                                    <input type="number" min="1" max="20" wire:model.blur="globalFieldLevelCapDraft"
                                        class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                </label>
                            </div>
                        </section>

                        @error('defaultUserAgent')
                            <p class="rounded-lg border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                        @enderror
                    </div>
                @elseif ($programSettingsTab === 'hero')
                    <div class="space-y-4">
                        <div class="grid gap-3 lg:grid-cols-3">
                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                <input type="checkbox" wire:model.change="globalHeroDefaultsDraft.adventures_enabled"
                                    class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                <span>Enable adventures</span>
                            </label>

                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                <input type="checkbox" wire:model.change="globalHeroDefaultsDraft.revive_enabled"
                                    class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                <span>Revive dead hero</span>
                            </label>

                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                <input type="checkbox" wire:model.change="globalHeroDefaultsDraft.attribute_upgrade_enabled"
                                    class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                <span>Upgrade attributes</span>
                            </label>
                        </div>

                        <label class="grid max-w-56 gap-1 text-sm">
                            <span class="font-medium text-[var(--color-ink)]">Minimum health</span>
                            <input type="number" min="0" max="100" wire:model.blur="globalHeroDefaultsDraft.min_health"
                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                        </label>

                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Attribute weights</h3>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ([
                                    'power' => 'Fighting',
                                    'offBonus' => 'Off bonus',
                                    'defBonus' => 'Def bonus',
                                    'productionPoints' => 'Resources',
                                ] as $attributeKey => $attributeLabel)
                                    <label wire:key="global-hero-weight-{{ $attributeKey }}" class="grid gap-1 text-sm">
                                        <span class="font-medium text-[var(--color-ink)]">{{ $attributeLabel }}</span>
                                        <input type="number" min="0" max="100"
                                            wire:model.blur="globalHeroDefaultsDraft.attribute_weights.{{ $attributeKey }}"
                                            class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @elseif ($programSettingsTab === 'merchants')
                    <div class="space-y-4">
                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Trading defaults</h3>
                            <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">Shared one-way travel-time limit used by automatic trade support and the TR quick-send panel.</p>

                            <div class="mt-4 grid max-w-sm gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Max one-way travel time</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="1" max="10080" step="1" wire:model.live.debounce.900ms="globalTradeMaxDurationMinutesDraft"
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-[var(--color-ink)] outline-none focus:ring-0">
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">minutes</span>
                                </div>
                                <span class="text-[11px] leading-4 text-[var(--color-muted)]">
                                    {{ intdiv(max(0, (int) $globalTradeMaxDurationMinutesDraft), 60) }}h {{ max(0, (int) $globalTradeMaxDurationMinutesDraft) % 60 }}m one-way.
                                </span>
                                @error('globalTradeMaxDurationMinutesDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </div>
                        </section>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-4 py-8 text-center text-sm font-semibold text-[var(--color-muted)]">
                        No global controls in this section yet.
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--color-line)] px-5 py-4">
                <button type="button" wire:click="closeProgramSettingsModal"
                    class="rounded-lg border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Cancel
                </button>
                <button type="button" wire:click="saveProgramSettings"
                    class="rounded-lg bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                    Save settings
                </button>
            </div>
        </div>
    </div>
@endif
