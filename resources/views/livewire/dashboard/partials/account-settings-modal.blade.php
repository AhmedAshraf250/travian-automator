@if ($showAccountSettingsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-[var(--color-line)] px-5 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Account settings</p>
                    <h2 class="mt-1 truncate font-[var(--font-display)] text-2xl font-semibold">{{ $editingAccountUsername ?: 'Account' }}</h2>
                </div>

                <button type="button" wire:click="closeAccountSettingsModal"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="border-b border-[var(--color-line)] px-5 pt-3">
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" wire:click="setAccountSettingsTab('account')"
                        class="rounded-t-lg border px-3 py-2 text-sm font-semibold transition {{ $accountSettingsTab === 'account' ? 'border-[var(--color-line)] border-b-[var(--color-panel)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-transparent bg-[var(--color-panel-alt)] text-[var(--color-muted)] hover:text-[var(--color-accent)]' }}">
                        Account Settings
                    </button>

                    <button type="button" wire:click="setAccountSettingsTab('hero')"
                        class="rounded-t-lg border px-3 py-2 text-sm font-semibold transition {{ $accountSettingsTab === 'hero' ? 'border-[var(--color-line)] border-b-[var(--color-panel)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-transparent bg-[var(--color-panel-alt)] text-[var(--color-muted)] hover:text-[var(--color-accent)]' }}">
                        Hero
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                @if ($accountSettingsTab === 'account')
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 rounded-lg bg-[var(--color-panel-alt)] px-4 py-3 text-sm font-semibold">
                            <input type="checkbox" wire:model.live="accountInheritUserAgentDraft"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Use program user agent</span>
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-semibold">Account user agent</span>
                            <textarea wire:model.live.debounce.500ms="accountUserAgentDraft" rows="5"
                                @disabled($accountInheritUserAgentDraft)
                                class="w-full rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-3 text-sm leading-6 text-[var(--color-ink)] outline-none transition placeholder:text-[var(--color-muted)] focus:border-[var(--color-accent)] focus:ring-4 focus:ring-[color:var(--color-accent-soft)] disabled:opacity-60"
                                placeholder="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ..."></textarea>
                        </label>

                        @error('accountUserAgentDraft')
                            <p class="rounded-lg border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                        @enderror

                        <label class="flex items-center gap-3 rounded-lg bg-[var(--color-panel-alt)] px-4 py-3 text-sm font-semibold">
                            <input type="checkbox" wire:model.live="accountAcceptQuestsDraft"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Collect task rewards</span>
                        </label>
                    </div>
                @else
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 rounded-lg bg-[var(--color-panel-alt)] px-4 py-3 text-sm font-semibold">
                            <input type="checkbox" wire:model.live="accountHeroUseGlobalSettingsDraft"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Use global settings</span>
                        </label>

                        <div class="grid gap-3 lg:grid-cols-3">
                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                <input type="checkbox" wire:model.live="accountHeroAdventuresEnabledDraft"
                                    @disabled($accountHeroUseGlobalSettingsDraft)
                                    class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-60">
                                <span>Enable hero adventures</span>
                            </label>

                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                <input type="checkbox" wire:model.live="accountHeroReviveEnabledDraft"
                                    @disabled($accountHeroUseGlobalSettingsDraft)
                                    class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-60">
                                <span>Revive dead hero</span>
                            </label>

                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                <input type="checkbox" wire:model.live="accountHeroAttributeUpgradeEnabledDraft"
                                    @disabled($accountHeroUseGlobalSettingsDraft)
                                    class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-60">
                                <span>Upgrade hero attributes</span>
                            </label>
                        </div>

                        <label class="grid max-w-56 gap-1 text-sm">
                            <span class="font-semibold text-[var(--color-ink)]">Hero health limit</span>
                            <input type="number" min="0" max="100" wire:model.live="accountHeroMinHealthDraft"
                                @disabled($accountHeroUseGlobalSettingsDraft)
                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)] disabled:opacity-60">
                        </label>

                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Attribute weights</h3>

                            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ([
                                    'power' => 'Fighting strength',
                                    'offBonus' => 'Off bonus',
                                    'defBonus' => 'Def bonus',
                                    'productionPoints' => 'Resources',
                                ] as $attributeKey => $attributeLabel)
                                    <label wire:key="account-hero-weight-{{ $attributeKey }}" class="grid gap-1 text-sm">
                                        <span class="font-medium text-[var(--color-ink)]">{{ $attributeLabel }}</span>
                                        <input type="number" min="0" max="100"
                                            wire:model.live="accountHeroAttributeWeightsDraft.{{ $attributeKey }}"
                                            @disabled($accountHeroUseGlobalSettingsDraft)
                                            class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)] disabled:opacity-60">
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @error('accountHeroMinHealthDraft')
                            <p class="rounded-lg border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--color-line)] px-5 py-4">
                <button type="button" wire:click="closeAccountSettingsModal"
                    class="rounded-lg border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Cancel
                </button>
                <button type="button" wire:click="saveAccountSettings"
                    class="rounded-lg bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                    Save settings
                </button>
            </div>
        </div>
    </div>
@endif
