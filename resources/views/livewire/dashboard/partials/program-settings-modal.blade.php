@if ($showProgramSettingsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="w-full max-w-3xl rounded-[2rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-6 shadow-[0_30px_100px_rgba(0,0,0,0.35)]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--color-muted)]">Program settings</p>
                    <h2 class="mt-2 font-[var(--font-display)] text-3xl">Shared defaults for all accounts.</h2>
                    <p class="mt-3 text-sm leading-6 text-[var(--color-muted)]">
                        These defaults apply only when an account does not already define its own dedicated runtime value.
                    </p>
                </div>

                <button type="button" wire:click="closeProgramSettingsModal" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="mt-6 space-y-4">
                <label class="block space-y-3">
                    <span class="text-sm font-semibold">Global fallback user agent</span>
                    <textarea
                        wire:model.live.debounce.500ms="defaultUserAgent"
                        rows="4"
                        class="w-full rounded-[1.5rem] border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-4 py-4 text-sm leading-6 text-[var(--color-ink)] outline-none transition placeholder:text-[var(--color-muted)] focus:border-[var(--color-accent)] focus:ring-4 focus:ring-[color:var(--color-accent-soft)]"
                        placeholder="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ..."
                    ></textarea>
                </label>

                <div class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4">
                    <div>
                        <h3 class="font-semibold text-[var(--color-ink)]">Global field priority</h3>
                        <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">
                            Villages that inherit program defaults use this order before their own building targets.
                        </p>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach (['wood' => 'Wood', 'clay' => 'Clay', 'iron' => 'Iron', 'crop' => 'Crop'] as $fieldKey => $fieldLabel)
                            <label wire:key="global-field-priority-{{ $fieldKey }}" class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">{{ $fieldLabel }}</span>
                                <select wire:model.live="globalFieldPriorityDraft.{{ $fieldKey }}"
                                    class="rounded-xl border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                    @foreach ([1, 2, 3, 4] as $priorityValue)
                                        <option value="{{ $priorityValue }}">{{ $priorityValue }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endforeach
                    </div>

                    <label class="mt-4 flex items-center gap-2 rounded-xl bg-[var(--color-panel)] px-3 py-2 text-sm">
                        <input type="checkbox" wire:model.live="globalPrioritizeCropFieldsWhenNegativeDraft"
                            class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                        <span>Prefer crop fields while crop production is negative</span>
                    </label>
                </div>

                <div class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4">
                    <div>
                        <h3 class="font-semibold text-[var(--color-ink)]">Global hero defaults</h3>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-3">
                        <label class="flex items-center gap-2 rounded-xl bg-[var(--color-panel)] px-3 py-2 text-sm">
                            <input type="checkbox" wire:model.live="globalHeroDefaultsDraft.adventures_enabled"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Enable adventures</span>
                        </label>

                        <label class="flex items-center gap-2 rounded-xl bg-[var(--color-panel)] px-3 py-2 text-sm">
                            <input type="checkbox" wire:model.live="globalHeroDefaultsDraft.revive_enabled"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Revive dead hero</span>
                        </label>

                        <label class="flex items-center gap-2 rounded-xl bg-[var(--color-panel)] px-3 py-2 text-sm">
                            <input type="checkbox" wire:model.live="globalHeroDefaultsDraft.attribute_upgrade_enabled"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Upgrade attributes</span>
                        </label>

                        <label class="grid gap-1 text-sm lg:col-span-1">
                            <span class="font-medium text-[var(--color-ink)]">Minimum health</span>
                            <input type="number" min="0" max="100" wire:model.live="globalHeroDefaultsDraft.min_health"
                                class="rounded-xl border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                        </label>

                        <div class="grid gap-2 sm:grid-cols-2 lg:col-span-2 lg:grid-cols-4">
                            @foreach ([
                                'power' => 'Fighting',
                                'offBonus' => 'Off bonus',
                                'defBonus' => 'Def bonus',
                                'productionPoints' => 'Resources',
                            ] as $attributeKey => $attributeLabel)
                                <label wire:key="global-hero-weight-{{ $attributeKey }}" class="grid gap-1 text-sm">
                                    <span class="font-medium text-[var(--color-ink)]">{{ $attributeLabel }}</span>
                                    <input type="number" min="0" max="100"
                                        wire:model.live="globalHeroDefaultsDraft.attribute_weights.{{ $attributeKey }}"
                                        class="rounded-xl border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                @error('defaultUserAgent')
                    <p class="rounded-2xl border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                @enderror

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4 text-sm text-[var(--color-muted)]">
                        If an account has its own user agent, that account-specific value still wins. The global fallback only fills the gap for accounts left blank.
                    </div>

                    <div class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4 text-sm text-[var(--color-muted)]">
                        This panel is intentionally small for now. More shared runtime settings can be added here later without mixing them into account-specific controls.
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <button type="button" wire:click="closeProgramSettingsModal" class="rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">Cancel</button>
                <button type="button" wire:click="saveProgramSettings" class="rounded-full bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_16px_40px_rgba(176,93,31,0.35)] transition hover:brightness-110">Save settings</button>
            </div>
        </div>
    </div>
@endif
