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

                    <button type="button" wire:click="setAccountSettingsTab('proxies')"
                        class="rounded-t-lg border px-3 py-2 text-sm font-semibold transition {{ $accountSettingsTab === 'proxies' ? 'border-[var(--color-line)] border-b-[var(--color-panel)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-transparent bg-[var(--color-panel-alt)] text-[var(--color-muted)] hover:text-[var(--color-accent)]' }}">
                        Proxies
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
                            <input type="checkbox" wire:model.change="accountAcceptQuestsDraft"
                                class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                            <span>Collect task rewards</span>
                        </label>
                    </div>
                @elseif ($accountSettingsTab === 'proxies')
                    <div class="space-y-4">
                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Proxy pool</h3>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">Use socks5h/socks4a when possible so DNS is resolved through the proxy endpoint.</p>
                                </div>

                                <button type="button" wire:click="addAccountProxyDraft"
                                    class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                                    Add proxy
                                </button>
                            </div>

                            <label class="mt-4 flex items-center gap-2 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] px-3 py-2 text-sm font-semibold">
                                <input type="radio" value="direct" wire:model.change="accountActiveProxyDraft"
                                    class="border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                <span>Direct connection</span>
                            </label>

                            <div class="mt-3 space-y-2">
                                @forelse ($accountProxyDrafts as $proxyIndex => $proxyDraft)
                                    @php
                                        $proxyCooldownTimestamp = ! empty($proxyDraft['cooldown_until']) ? \Carbon\CarbonImmutable::parse($proxyDraft['cooldown_until'])->getTimestamp() * 1000 : 0;
                                        $proxyFailureThreshold = max(1, (int) config('travian.proxy_pool.failure_threshold', 5));
                                        $proxyFailureCount = (int) ($proxyDraft['failure_count'] ?? 0);
                                        $proxyLifetimeFailureCount = (int) ($proxyDraft['lifetime_failure_count'] ?? 0);
                                    @endphp
                                    <div wire:key="account-proxy-draft-{{ $proxyIndex }}"
                                        class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-3">
                                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-[4rem_5.75rem_minmax(8rem,1fr)_4.75rem_minmax(7rem,1fr)_minmax(7rem,1fr)_6rem_2.75rem]">
                                            <label class="flex h-9 items-center gap-2 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 text-xs font-semibold text-[var(--color-muted)]">
                                                <input type="radio"
                                                    value="{{ isset($proxyDraft['id']) && $proxyDraft['id'] ? 'proxy:' . $proxyDraft['id'] : 'new:' . $proxyIndex }}"
                                                    wire:model.change="accountActiveProxyDraft"
                                                    class="border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                                Use
                                            </label>

                                            <select wire:model.change="accountProxyDrafts.{{ $proxyIndex }}.scheme"
                                                title="Proxy protocol"
                                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-2 py-2 text-xs font-semibold text-[var(--color-ink)] outline-none focus:border-[var(--color-accent)]">
                                                <option value="http">http</option>
                                                <option value="https">https</option>
                                                <option value="socks4">socks4</option>
                                                <option value="socks4a">socks4a</option>
                                                <option value="socks5">socks5</option>
                                                <option value="socks5h">socks5h</option>
                                            </select>

                                            <input type="text" wire:model.live.debounce.400ms="accountProxyDrafts.{{ $proxyIndex }}.host"
                                                title="Proxy host or IP"
                                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-2 text-xs text-[var(--color-ink)] outline-none focus:border-[var(--color-accent)]"
                                                placeholder="1.2.3.4">

                                            <input type="number" min="1" max="65535" wire:model.blur="accountProxyDrafts.{{ $proxyIndex }}.port"
                                                title="Proxy port"
                                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-2 text-xs text-[var(--color-ink)] outline-none focus:border-[var(--color-accent)]"
                                                placeholder="1080">

                                            <input type="text" wire:model.live.debounce.400ms="accountProxyDrafts.{{ $proxyIndex }}.username"
                                                title="Optional proxy username"
                                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-2 text-xs text-[var(--color-ink)] outline-none focus:border-[var(--color-accent)]"
                                                placeholder="proxy user">

                                            <input type="password" wire:model.live.debounce.400ms="accountProxyDrafts.{{ $proxyIndex }}.password"
                                                title="Optional proxy password. Leave empty to keep the saved password."
                                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-2 text-xs text-[var(--color-ink)] outline-none focus:border-[var(--color-accent)]"
                                                placeholder="{{ isset($proxyDraft['id']) && $proxyDraft['id'] ? 'keep saved' : 'proxy password' }}">

                                            <select wire:model.change="accountProxyDrafts.{{ $proxyIndex }}.status"
                                                class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-2 py-2 text-xs font-semibold text-[var(--color-ink)] outline-none focus:border-[var(--color-accent)]">
                                                <option value="active">Ready</option>
                                                <option value="disabled">Paused</option>
                                                <option value="cooldown">Cooling</option>
                                            </select>

                                            <button type="button" wire:click="removeAccountProxyDraft({{ $proxyIndex }})"
                                                title="Remove proxy"
                                                class="inline-flex h-9 items-center justify-center rounded-lg border border-rose-500/30 px-2 text-sm font-bold text-rose-900 transition hover:bg-rose-500/10">
                                                ×
                                            </button>
                                        </div>

                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold text-[var(--color-muted)]">
                                            <span class="rounded-md bg-[var(--color-panel-alt)] px-2 py-1">
                                                current fail {{ $proxyFailureCount }}/{{ $proxyFailureThreshold }}
                                            </span>
                                            <span class="rounded-md bg-[var(--color-panel-alt)] px-2 py-1">
                                                lifetime fail {{ $proxyLifetimeFailureCount }}
                                            </span>

                                            @if (($proxyDraft['status'] ?? null) === \App\Models\AccountProxy::StatusCooldown && $proxyCooldownTimestamp > 0)
                                                <span
                                                    class="rounded-md border border-amber-500/25 bg-amber-500/10 px-2 py-1 text-amber-950"
                                                    x-data="{
                                                        endsAt: {{ $proxyCooldownTimestamp }},
                                                        remaining: 0,
                                                        intervalId: null,
                                                        init() {
                                                            this.tick();
                                                            this.intervalId = setInterval(() => this.tick(), 1000);
                                                        },
                                                        destroy() {
                                                            if (this.intervalId !== null) {
                                                                clearInterval(this.intervalId);
                                                            }
                                                        },
                                                        tick() {
                                                            this.remaining = Math.max(0, Math.ceil((this.endsAt - Date.now()) / 1000));
                                                        },
                                                        label() {
                                                            if (this.remaining <= 0) {
                                                                return 'ready on next check';
                                                            }

                                                            const minutes = Math.floor(this.remaining / 60);
                                                            const seconds = String(this.remaining % 60).padStart(2, '0');

                                                            return `cooling ${minutes}:${seconds}`;
                                                        }
                                                    }"
                                                    x-text="label()">
                                                    Cooling
                                                </span>
                                            @endif

                                            @if (! empty($proxyDraft['last_error_message']))
                                                <span class="max-w-full truncate rounded-md bg-rose-500/10 px-2 py-1 text-rose-900"
                                                    title="{{ $proxyDraft['last_error_message'] }}">
                                                    {{ \Illuminate\Support\Str::limit($proxyDraft['last_error_message'], 80) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="rounded-lg border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-4 py-5 text-sm text-[var(--color-muted)]">
                                        No extra proxies yet.
                                    </p>
                                @endforelse
                            </div>
                        </div>

                        @error('accountProxyDrafts.*.host')
                            <p class="rounded-lg border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                        @enderror
                        @error('accountProxyDrafts.*.port')
                            <p class="rounded-lg border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    @php
                        $heroAttributeLabels = [
                            'power' => 'Fighting strength',
                            'offBonus' => 'Off bonus',
                            'defBonus' => 'Def bonus',
                            'productionPoints' => 'Resources',
                        ];
                        $programHeroDefaults = is_array($globalHeroDefaults ?? null) ? $globalHeroDefaults : [];
                        $programHeroWeights = is_array($programHeroDefaults['attribute_weights'] ?? null)
                            ? $programHeroDefaults['attribute_weights']
                            : [];
                        $heroModeIsGlobal = (bool) $accountHeroUseGlobalSettingsDraft;
                    @endphp
                    <div class="space-y-4">
                        <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Hero automation mode</h3>
                                    <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">
                                        Choose whether this account follows Program Hero settings or uses its own overrides.
                                    </p>
                                </div>

                                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $heroModeIsGlobal ? 'bg-sky-500/10 text-sky-800' : 'bg-emerald-500/10 text-emerald-800' }}">
                                    {{ $heroModeIsGlobal ? 'Inherited from Program' : 'Account override active' }}
                                </span>
                            </div>

                            <div class="mt-4 grid gap-2 md:grid-cols-2">
                                <button type="button" wire:click="$set('accountHeroUseGlobalSettingsDraft', true)"
                                    class="rounded-lg border px-3 py-3 text-left transition {{ $heroModeIsGlobal ? 'border-[var(--color-accent)] bg-[var(--color-panel)] ring-4 ring-[color:var(--color-accent-soft)]' : 'border-[var(--color-line)] bg-[var(--color-panel)] hover:border-[var(--color-accent)]' }}">
                                    <span class="block text-sm font-semibold text-[var(--color-ink)]">Use Program Hero settings</span>
                                    <span class="mt-1 block text-xs leading-5 text-[var(--color-muted)]">This account will use the effective values shown below.</span>
                                </button>

                                <button type="button" wire:click="$set('accountHeroUseGlobalSettingsDraft', false)"
                                    class="rounded-lg border px-3 py-3 text-left transition {{ ! $heroModeIsGlobal ? 'border-[var(--color-accent)] bg-[var(--color-panel)] ring-4 ring-[color:var(--color-accent-soft)]' : 'border-[var(--color-line)] bg-[var(--color-panel)] hover:border-[var(--color-accent)]' }}">
                                    <span class="block text-sm font-semibold text-[var(--color-ink)]">Customize this account</span>
                                    <span class="mt-1 block text-xs leading-5 text-[var(--color-muted)]">The controls below become the account's saved Hero policy.</span>
                                </button>
                            </div>
                        </section>

                        @if ($heroModeIsGlobal)
                            <section class="rounded-lg border border-sky-600/20 bg-sky-500/10 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-sky-950">Effective Program Hero settings</h3>
                                    <span class="rounded-full bg-white/70 px-2.5 py-1 text-[11px] font-semibold text-sky-900">Used now</span>
                                </div>

                                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                    <span class="rounded-md bg-white/70 px-3 py-2 text-xs font-semibold text-sky-950">
                                        Adventures: {{ (bool) ($programHeroDefaults['adventures_enabled'] ?? false) ? 'On' : 'Off' }}
                                    </span>
                                    <span class="rounded-md bg-white/70 px-3 py-2 text-xs font-semibold text-sky-950">
                                        Revive: {{ (bool) ($programHeroDefaults['revive_enabled'] ?? false) ? 'On' : 'Off' }}
                                    </span>
                                    <span class="rounded-md bg-white/70 px-3 py-2 text-xs font-semibold text-sky-950">
                                        Attributes: {{ (bool) ($programHeroDefaults['attribute_upgrade_enabled'] ?? false) ? 'On' : 'Off' }}
                                    </span>
                                    <span class="rounded-md bg-white/70 px-3 py-2 text-xs font-semibold text-sky-950">
                                        Health limit: {{ (int) ($programHeroDefaults['min_health'] ?? 40) }}%
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($heroAttributeLabels as $attributeKey => $attributeLabel)
                                        <span wire:key="program-hero-effective-{{ $attributeKey }}"
                                            class="rounded-md bg-white/70 px-2.5 py-1 text-[11px] font-semibold text-sky-900">
                                            {{ $attributeLabel }} {{ (int) ($programHeroWeights[$attributeKey] ?? 0) }}
                                        </span>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @unless ($heroModeIsGlobal)
                            <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Account Hero overrides</h3>
                                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-800">
                                        Active
                                    </span>
                                </div>

                                <div class="mt-3 grid gap-3 lg:grid-cols-3">
                                    <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                        <input type="checkbox" wire:model.change="accountHeroAdventuresEnabledDraft"
                                            class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                        <span>Enable hero adventures</span>
                                    </label>

                                    <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                        <input type="checkbox" wire:model.change="accountHeroReviveEnabledDraft"
                                            class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                        <span>Revive dead hero</span>
                                    </label>

                                    <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel-alt)] px-3 py-3 text-sm">
                                        <input type="checkbox" wire:model.change="accountHeroAttributeUpgradeEnabledDraft"
                                            class="rounded border-[var(--color-line-strong)] text-[var(--color-accent)]">
                                        <span>Upgrade hero attributes</span>
                                    </label>
                                </div>

                                <label class="mt-3 grid max-w-56 gap-1 text-sm">
                                    <span class="font-semibold text-[var(--color-ink)]">Hero health limit</span>
                                    <input type="number" min="0" max="100" wire:model.blur="accountHeroMinHealthDraft"
                                        class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                </label>

                                <div class="mt-4 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                                    <h3 class="text-sm font-semibold text-[var(--color-ink)]">Attribute weights</h3>

                                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ($heroAttributeLabels as $attributeKey => $attributeLabel)
                                            <label wire:key="account-hero-weight-{{ $attributeKey }}" class="grid gap-1 text-sm">
                                                <span class="font-medium text-[var(--color-ink)]">{{ $attributeLabel }}</span>
                                                <input type="number" min="0" max="100"
                                                    wire:model.blur="accountHeroAttributeWeightsDraft.{{ $attributeKey }}"
                                                    class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 py-2 text-sm text-[var(--color-ink)] outline-none transition focus:border-[var(--color-accent)]">
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </section>
                        @endunless

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
