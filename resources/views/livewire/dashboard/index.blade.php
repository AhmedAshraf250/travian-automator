<div class="relative overflow-hidden">
    <div
        class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,rgba(224,152,65,0.35),transparent_60%)]">
    </div>
    <div
        class="pointer-events-none absolute inset-y-24 right-[-6rem] w-72 rounded-full bg-[radial-gradient(circle,rgba(100,160,120,0.3),transparent_65%)] blur-3xl">
    </div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <header
            class="flex flex-col gap-4 rounded-[2rem] border border-[var(--color-line)] bg-[var(--color-panel)]/85 p-6 shadow-[0_24px_80px_rgba(20,18,10,0.18)] backdrop-blur md:flex-row md:items-end md:justify-between">
            <div class="max-w-3xl space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--color-muted)]">Travian
                    Multi-Account Automation</p>
                <div class="space-y-2">
                    <h1 class="font-[var(--font-display)] text-3xl leading-none sm:text-4xl lg:text-5xl">Control center
                        for isolated accounts, villages, and future automation flows.</h1>
                    <p class="max-w-2xl text-sm leading-6 text-[var(--color-muted)] sm:text-base">
                        The first slice is now focused on clean data foundations, persisted bulk import, and a dashboard
                        that can grow into the sync, parser, and resource simulation engine.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" wire:click="openImportModal"
                    class="inline-flex items-center justify-center rounded-full bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_16px_40px_rgba(176,93,31,0.35)] transition hover:brightness-110">
                    Bulk import accounts
                </button>
            </div>
        </header>

        @if (session('dashboard-banner'))
            <div
                class="rounded-[1.5rem] border border-emerald-700/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-900">
                {{ session('dashboard-banner') }}
            </div>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--color-muted)]">Accounts</p>
                <p class="mt-4 font-[var(--font-display)] text-4xl">{{ $stats['accounts'] }}</p>
                <p class="mt-2 text-sm text-[var(--color-muted)]">Imported identities with isolated transport settings.
                </p>
            </article>

            <article
                class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--color-muted)]">Active</p>
                <p class="mt-4 font-[var(--font-display)] text-4xl">{{ $stats['activeAccounts'] }}</p>
                <p class="mt-2 text-sm text-[var(--color-muted)]">Accounts currently allowed to run automation work.</p>
            </article>

            <article
                class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--color-muted)]">Villages</p>
                <p class="mt-4 font-[var(--font-display)] text-4xl">{{ $stats['villages'] }}</p>
                <p class="mt-2 text-sm text-[var(--color-muted)]">Synced villages will appear under each account row.
                </p>
            </article>

            <article
                class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--color-muted)]">Syncing</p>
                <p class="mt-4 font-[var(--font-display)] text-4xl">{{ $stats['syncing'] }}</p>
                <p class="mt-2 text-sm text-[var(--color-muted)]">Manual sync requests staged from the dashboard.</p>
            </article>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-[var(--font-display)] text-2xl">Accounts</h2>
                    <p class="text-sm text-[var(--color-muted)]">Each account remains isolated by credentials, cookies,
                        user agent, and optional proxy.</p>
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($accounts as $account)
                    <article
                        class="overflow-hidden rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
                        <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" wire:click="toggleAccountExpansion({{ $account->id }})"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-accent)] transition hover:border-[var(--color-accent)]">
                                        {{ $expandedAccounts[$account->id] ?? false ? '−' : '+' }}
                                    </button>

                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-lg font-semibold">{{ $account->username }}</h3>
                                            <span
                                                class="rounded-full px-3 py-1 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-500/15 text-emerald-900' : 'bg-stone-800/10 text-stone-700' }}">
                                                {{ $account->status->value }}
                                            </span>
                                        </div>

                                        <p class="text-sm text-[var(--color-muted)]">{{ $account->server_url }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">Proxy
                                        </p>
                                        <p class="mt-2 text-sm font-medium text-[var(--color-ink)]">
                                            {{ $account->proxy_ip ? "{$account->proxy_ip}:{$account->proxy_port}" : 'Direct connection' }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">User
                                            agent</p>
                                        <p class="mt-2 line-clamp-2 text-sm font-medium text-[var(--color-ink)]">
                                            {{ $account->user_agent ?: 'Not assigned yet' }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                            Villages</p>
                                        <p class="mt-2 text-sm font-medium text-[var(--color-ink)]">
                                            {{ $account->villages_count }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">Last
                                            sync</p>
                                        <p class="mt-2 text-sm font-medium text-[var(--color-ink)]">
                                            {{ $account->last_sync_at?->diffForHumans() ?? 'Never synced' }}</p>
                                    </div>
                                </div>

                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:max-w-xl lg:justify-end">
                                @if ($account->is_active)
                                    <button type="button" wire:click="pauseAccount({{ $account->id }})"
                                        class="rounded-full border border-amber-800/20 bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-900 transition hover:bg-amber-500/20">Pause</button>
                                @else
                                    <button type="button" wire:click="activateAccount({{ $account->id }})"
                                        class="rounded-full border border-emerald-800/20 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-500/20">Activate</button>
                                @endif

                                <button type="button" wire:click="requestAccountSync({{ $account->id }})"
                                    class="rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">Update
                                    now</button>
                                <button type="button"
                                    class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Village
                                    settings</button>
                                <button type="button"
                                    class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Train
                                    troops</button>
                                <button type="button"
                                    class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Send
                                    resources</button>
                            </div>
                        </div>

                        @if ($expandedAccounts[$account->id] ?? false)
                            <div class="border-t border-[var(--color-line)] bg-[var(--color-panel-alt)]/50 px-5 py-4">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <div>
                                        <h4 class="font-semibold">Villages</h4>
                                        <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                            Prepared for wide resource, storage, and troop data</p>
                                    </div>
                                    <p class="text-xs text-[var(--color-muted)]">Horizontal scrolling is enabled on
                                        small screens.</p>
                                </div>

                                <div class="space-y-3">
                                    @forelse ($account->villages as $village)
                                        <div wire:key="village-row-{{ $village->id }}"
                                            class="overflow-x-auto rounded-[1.5rem] border border-[var(--color-line)] bg-[var(--color-panel)]">
                                            <div class="min-w-[1180px] p-4">
                                                <div
                                                    class="grid gap-4 xl:grid-cols-[minmax(240px,1.1fr)_minmax(620px,2.2fr)_minmax(260px,0.9fr)] xl:items-start">
                                                    <div
                                                        class="rounded-[1.25rem] bg-[var(--color-panel-alt)] px-4 py-4">
                                                        <p class="text-base font-semibold">{{ $village->name }}</p>
                                                        <p class="mt-1 text-xs text-[var(--color-muted)]">
                                                            {{ $village->x !== null && $village->y !== null ? "({$village->x}, {$village->y})" : 'Coordinates pending sync' }}
                                                        </p>

                                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                                            <div>
                                                                <p
                                                                    class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                    Population</p>
                                                                <p class="mt-1 text-sm font-medium">
                                                                    {{ $village->population }}</p>
                                                            </div>
                                                            <div>
                                                                <p
                                                                    class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                    Status</p>
                                                                <p class="mt-1 text-sm font-medium">
                                                                    {{ $village->is_active ? 'Active' : 'Paused' }}</p>
                                                            </div>
                                                            <div>
                                                                <p
                                                                    class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                    Capital</p>
                                                                <p class="mt-1 text-sm font-medium">
                                                                    {{ $village->is_capital ? 'Yes' : 'No' }}</p>
                                                            </div>
                                                            <div>
                                                                <p
                                                                    class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                    Last sync</p>
                                                                <p class="mt-1 text-sm font-medium">
                                                                    {{ $village->last_sync_at?->diffForHumans() ?? 'Waiting for first sync' }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-4">
                                                        <div
                                                            class="rounded-[1.25rem] bg-[var(--color-panel-alt)] px-4 py-4">
                                                            <p
                                                                class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                Current resources</p>
                                                            <div class="mt-3 space-y-2 text-sm">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Wood</span><span
                                                                        class="font-medium">{{ $village->resourceState?->wood ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Clay</span><span
                                                                        class="font-medium">{{ $village->resourceState?->clay ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Iron</span><span
                                                                        class="font-medium">{{ $village->resourceState?->iron ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Crop</span><span
                                                                        class="font-medium">{{ $village->resourceState?->crop ?? 0 }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="rounded-[1.25rem] bg-[var(--color-panel-alt)] px-4 py-4">
                                                            <p
                                                                class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                Production per hour</p>
                                                            <div class="mt-3 space-y-2 text-sm">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Wood/h</span><span
                                                                        class="font-medium">{{ $village->resourceState?->wood_production ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Clay/h</span><span
                                                                        class="font-medium">{{ $village->resourceState?->clay_production ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Iron/h</span><span
                                                                        class="font-medium">{{ $village->resourceState?->iron_production ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Crop/h</span><span
                                                                        class="font-medium">{{ $village->resourceState?->crop_production ?? 0 }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="rounded-[1.25rem] bg-[var(--color-panel-alt)] px-4 py-4">
                                                            <p
                                                                class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                Storage</p>
                                                            <div class="mt-3 space-y-2 text-sm">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Warehouse</span><span
                                                                        class="font-medium">{{ $village->resourceState?->warehouse_capacity ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Granary</span><span
                                                                        class="font-medium">{{ $village->resourceState?->granary_capacity ?? 0 }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Support mode</span><span
                                                                        class="font-medium">{{ $village->settings?->support_enabled ? 'Enabled' : 'Off' }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Trade mode</span><span
                                                                        class="font-medium">{{ $village->settings?->trade_mode?->value ?? 'balanced' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="rounded-[1.25rem] bg-[var(--color-panel-alt)] px-4 py-4">
                                                            <p
                                                                class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                Troops snapshot</p>
                                                            <div
                                                                class="mt-3 rounded-xl border border-[var(--color-line)] bg-[var(--color-panel)] px-3 py-3 font-mono text-xs leading-6 text-[var(--color-ink)]">
                                                                [ hero | 0 | 2 | 55 | 4 | 22 | 434 | 34 | 33 | 3 | 0 ]
                                                            </div>
                                                            <p class="mt-2 text-[11px] text-[var(--color-muted)]">
                                                                Fixed troop-slot pattern placeholder for tribe snapshots
                                                                until live troop parsing is added.
                                                            </p>
                                                        </div>

                                                        <div
                                                            class="rounded-[1.25rem] bg-[var(--color-panel-alt)] px-4 py-4">
                                                            <p
                                                                class="text-[11px] uppercase tracking-[0.18em] text-[var(--color-muted)]">
                                                                Runtime</p>
                                                            <div class="mt-3 space-y-2 text-sm">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Queue state</span><span
                                                                        class="font-medium">{{ $account->status->value }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Village sync</span><span
                                                                        class="font-medium">{{ $village->last_sync_at?->diffForHumans() ?? 'Pending' }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Support</span><span
                                                                        class="font-medium">{{ $village->settings?->support_enabled ? 'On' : 'Off' }}</span>
                                                                </div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <span>Send mode</span><span
                                                                        class="font-medium">{{ $village->settings?->send_enabled ? 'On' : 'Off' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-wrap items-start gap-2 xl:justify-end">
                                                        <button type="button"
                                                            wire:click="toggleVillage({{ $village->id }})"
                                                            class="rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                                                            {{ $village->is_active ? 'Pause' : 'Activate' }}
                                                        </button>
                                                        <button type="button"
                                                            wire:click="requestVillageSync({{ $village->id }})"
                                                            class="rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">Update
                                                            now</button>
                                                        <button type="button"
                                                            class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Settings</button>
                                                        <button type="button"
                                                            class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Train</button>
                                                        <button type="button"
                                                            class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Send</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div
                                            class="rounded-[1.5rem] border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-4 py-5 text-sm text-[var(--color-muted)]">
                                            No villages are stored for this account yet. Once the sync layer is
                                            connected, villages and their resource states will appear here.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </article>
                @empty
                    <div
                        class="rounded-[1.75rem] border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-6 py-8 text-center shadow-[0_18px_50px_rgba(24,20,12,0.08)]">
                        <h3 class="font-[var(--font-display)] text-2xl">No accounts imported yet</h3>
                        <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-[var(--color-muted)]">
                            Start with the legacy import format. The draft is persisted securely, so even if the page
                            refreshes your latest input stays available.
                        </p>
                        <button type="button" wire:click="openImportModal"
                            class="mt-6 inline-flex items-center justify-center rounded-full bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_16px_40px_rgba(176,93,31,0.35)] transition hover:brightness-110">
                            Add the first accounts
                        </button>
                    </div>
                @endforelse
            </div>
        </section>

        @if ($showActivityLog)
            <section
                class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)] lg:sticky lg:bottom-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-[var(--font-display)] text-2xl">Activity log</h2>
                        <p class="text-sm text-[var(--color-muted)]">Footer-style activity block with an internal
                            scroll area that stays independent from the page flow.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="rounded-full bg-[var(--color-panel-alt)] px-3 py-1 text-xs font-semibold text-[var(--color-muted)]">{{ $activityLogs->count() }}
                            rows</span>
                        <button type="button" wire:click="toggleActivityLog"
                            class="inline-flex items-center justify-center rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold text-[var(--color-ink)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                            Hide log
                        </button>
                    </div>
                </div>

                <div
                    class="mt-5 h-[24rem] overflow-y-scroll overscroll-contain rounded-[1.5rem] border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-3">
                    <div class="space-y-3">
                        @forelse ($activityLogs as $activityLog)
                            <article wire:key="activity-log-{{ $activityLog->id }}"
                                class="rounded-[1.25rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold">
                                            {{ $activityLog->message ?: ucfirst($activityLog->activity_type->value) }}
                                        </p>
                                        <p class="mt-1 text-xs text-[var(--color-muted)]">
                                            {{ $activityLog->account?->username ?? 'System' }}
                                            @if ($activityLog->village)
                                                · {{ $activityLog->village->name }}
                                            @endif
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] {{ $activityLog->status->value === 'failed' ? 'bg-rose-500/15 text-rose-900' : 'bg-stone-900/5 text-stone-700' }}">
                                        {{ $activityLog->status->value }}
                                    </span>
                                </div>

                                <div class="mt-3 flex items-center justify-between text-xs text-[var(--color-muted)]">
                                    <span>{{ $activityLog->activity_type->value }}</span>
                                    <span>{{ $activityLog->executed_at?->diffForHumans() ?? $activityLog->created_at->diffForHumans() }}</span>
                                </div>
                            </article>
                        @empty
                            <div
                                class="rounded-[1.25rem] border border-dashed border-[var(--color-line-strong)] px-4 py-5 text-sm text-[var(--color-muted)]">
                                Activity entries will appear here as import, sync, build, and manual actions are
                                recorded.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        @else
            <section
                class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-[var(--font-display)] text-2xl">Activity log</h2>
                        <p class="text-sm text-[var(--color-muted)]">The log block is hidden, but still reserved as an
                            independent area in the layout.</p>
                    </div>
                    <button type="button" wire:click="toggleActivityLog"
                        class="inline-flex items-center justify-center rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold text-[var(--color-ink)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                        Show log
                    </button>
                </div>
            </section>
        @endif
    </div>

    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/55 px-4 py-6 backdrop-blur-sm">
            <div
                class="w-full max-w-3xl rounded-[2rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-6 shadow-[0_30px_100px_rgba(0,0,0,0.35)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--color-muted)]">Bulk
                            import</p>
                        <h2 class="mt-2 font-[var(--font-display)] text-3xl">Paste multiple account lines once.</h2>
                        <p class="mt-3 text-sm leading-6 text-[var(--color-muted)]">
                            Supported format:
                            <code
                                class="rounded bg-[var(--color-panel-alt)] px-2 py-1 text-xs">!server!username!password!proxy_ip!proxy_port!user_agent</code>
                        </p>
                    </div>

                    <button type="button" wire:click="closeImportModal"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                        ×
                    </button>
                </div>

                <div class="mt-6 space-y-4">
                    <label class="block space-y-3">
                        <span class="text-sm font-semibold">Textarea draft</span>
                        <textarea wire:model.live.debounce.500ms="bulkImportDraft" rows="12"
                            class="w-full rounded-[1.5rem] border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-4 py-4 text-sm leading-6 text-[var(--color-ink)] outline-none transition placeholder:text-[var(--color-muted)] focus:border-[var(--color-accent)] focus:ring-4 focus:ring-[color:var(--color-accent-soft)]"
                            placeholder="!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0 ..."></textarea>
                    </label>

                    @error('bulkImportDraft')
                        <p class="rounded-2xl border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">
                            {{ $message }}</p>
                    @enderror

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div
                            class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4 text-sm text-[var(--color-muted)]">
                            The latest contents are saved in the project database as an encrypted draft so a refresh
                            does not force you to paste everything again.
                        </div>

                        <div
                            class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4 text-sm text-[var(--color-muted)]">
                            Passwords and persisted cookies are stored using Laravel encrypted casts, and each imported
                            account is prepared for isolated transport settings.
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button type="button" wire:click="closeImportModal"
                        class="rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">Cancel</button>
                    <button type="button" wire:click="importAccounts"
                        class="rounded-full bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_16px_40px_rgba(176,93,31,0.35)] transition hover:brightness-110">Parse
                        &amp; import</button>
                </div>
            </div>
        </div>
    @endif
</div>
