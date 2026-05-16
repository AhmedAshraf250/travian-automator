@php
    $shouldPollDashboard = ! $showProgramSettingsModal && ! $showImportModal && ! $showVillageBuildPlanModal;
@endphp

<div class="relative overflow-hidden">
    <div
        class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-[radial-gradient(circle_at_top,rgba(224,152,65,0.28),transparent_60%)]">
    </div>
    <div
        class="pointer-events-none absolute right-[-5rem] top-20 h-56 w-56 rounded-full bg-[radial-gradient(circle,rgba(100,160,120,0.2),transparent_65%)] blur-3xl">
    </div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-[118rem] flex-col gap-5 px-3 py-4 sm:px-5 lg:px-6 2xl:px-8"
        @if ($shouldPollDashboard) wire:poll.5s.keep-alive @endif>
        <header
            class="flex flex-col gap-4 rounded-[1.6rem] border border-[var(--color-line)] bg-[var(--color-panel)]/90 p-5 shadow-[0_20px_60px_rgba(20,18,10,0.12)] backdrop-blur md:flex-row md:items-end md:justify-between">
            <div class="max-w-4xl space-y-2.5">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[var(--color-muted)]">Travian
                        Multi-Account Automation</p>
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-3 py-1 text-[11px] font-semibold text-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live refresh 5s
                    </span>
                    <span
                        class="rounded-full bg-[var(--color-panel-alt)] px-3 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                        Updated {{ now()->format('H:i:s') }}
                    </span>
                    <span wire:loading.delay
                        class="rounded-full border border-sky-500/25 bg-sky-500/10 px-3 py-1 text-[11px] font-semibold text-sky-800">
                        Refreshing…
                    </span>
                </div>

                <div class="space-y-1.5">
                    <h1 class="font-[var(--font-display)] text-2xl leading-none sm:text-3xl lg:text-[2.3rem]">
                        Lean live view for accounts, villages, queues, and background actions.
                    </h1>
                    <p class="max-w-3xl text-sm leading-6 text-[var(--color-muted)]">
                        Recent syncs and construction changes now flow back into the same village row without waiting
                        for a manual overview refresh.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" wire:click="toggleAutomation"
                    class="inline-flex items-center justify-center rounded-full border px-4 py-2 text-sm font-semibold transition {{ ($automationEnabled ?? true) ? 'border-emerald-700/20 bg-emerald-500/10 text-emerald-900 hover:bg-emerald-500/20' : 'border-amber-700/20 bg-amber-500/10 text-amber-900 hover:bg-amber-500/20' }}">
                    {{ ($automationEnabled ?? true) ? 'Program ON' : 'Program OFF' }}
                </button>
                <button type="button" wire:click="openProgramSettingsModal"
                    class="inline-flex items-center justify-center rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Program settings
                </button>
                <button type="button" wire:click="openImportModal"
                    class="inline-flex items-center justify-center rounded-full bg-[var(--color-accent)] px-4 py-2 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_14px_32px_rgba(176,93,31,0.28)] transition hover:brightness-110">
                    Import accounts
                </button>
            </div>
        </header>

        @if (session('dashboard-banner'))
            <div
                class="rounded-[1.25rem] border border-emerald-700/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900">
                {{ session('dashboard-banner') }}
            </div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
                class="rounded-[1.35rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-4 shadow-[0_14px_36px_rgba(24,20,12,0.08)]">
                <p class="text-[11px] uppercase tracking-[0.2em] text-[var(--color-muted)]">Accounts</p>
                <p class="mt-2 font-[var(--font-display)] text-3xl">{{ $stats['accounts'] }}</p>
                <p class="mt-1 text-xs text-[var(--color-muted)]">Imported account identities.</p>
            </article>

            <article
                class="rounded-[1.35rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-4 shadow-[0_14px_36px_rgba(24,20,12,0.08)]">
                <p class="text-[11px] uppercase tracking-[0.2em] text-[var(--color-muted)]">Active</p>
                <p class="mt-2 font-[var(--font-display)] text-3xl">{{ $stats['activeAccounts'] }}</p>
                <p class="mt-1 text-xs text-[var(--color-muted)]">Accounts allowed to run automation.</p>
            </article>

            <article
                class="rounded-[1.35rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-4 shadow-[0_14px_36px_rgba(24,20,12,0.08)]">
                <p class="text-[11px] uppercase tracking-[0.2em] text-[var(--color-muted)]">Villages</p>
                <p class="mt-2 font-[var(--font-display)] text-3xl">{{ $stats['villages'] }}</p>
                <p class="mt-1 text-xs text-[var(--color-muted)]">Live rows inside expanded accounts.</p>
            </article>

            <article
                class="rounded-[1.35rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-4 shadow-[0_14px_36px_rgba(24,20,12,0.08)]">
                <p class="text-[11px] uppercase tracking-[0.2em] text-[var(--color-muted)]">Syncing</p>
                <p class="mt-2 font-[var(--font-display)] text-3xl">{{ $stats['syncing'] }}</p>
                <p class="mt-1 text-xs text-[var(--color-muted)]">Accounts currently waiting for sync work.</p>
            </article>
        </section>

        <section
            class="rounded-[1.35rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-4 shadow-[0_14px_36px_rgba(24,20,12,0.06)]">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-[var(--color-muted)]">Program defaults</p>
                    <h2 class="mt-1 font-[var(--font-display)] text-xl">Shared runtime profile</h2>
                </div>

                <div class="grid gap-2 sm:grid-cols-3 lg:min-w-[44rem]">
                    <div class="rounded-[1rem] bg-[var(--color-panel-alt)] px-3 py-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-[var(--color-muted)]">Automation</p>
                        <p class="mt-1 text-sm font-medium text-[var(--color-ink)]">
                            {{ ($automationEnabled ?? true) ? 'Execution allowed' : 'Read-only mode' }}
                        </p>
                    </div>

                    <div class="rounded-[1rem] bg-[var(--color-panel-alt)] px-3 py-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-[var(--color-muted)]">User agent</p>
                        <p class="mt-1 line-clamp-2 text-sm font-medium text-[var(--color-ink)]">
                            {{ $globalDefaultUserAgent ?: 'No fallback configured' }}
                        </p>
                    </div>

                    <div class="rounded-[1rem] bg-[var(--color-panel-alt)] px-3 py-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-[var(--color-muted)]">Refresh mode</p>
                        <p class="mt-1 text-sm font-medium text-[var(--color-ink)]">
                            {{ $shouldPollDashboard ? 'Polling every 5s' : 'Paused while a modal is open' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-[var(--font-display)] text-xl">Accounts</h2>
                    <p class="text-sm text-[var(--color-muted)]">Compact account overview with expandable village rows.
                    </p>
                </div>

                <span
                    class="rounded-full bg-[var(--color-panel-alt)] px-3 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                    {{ $accounts->count() }} account rows
                </span>
            </div>

            <div class="space-y-3">
                @forelse ($accounts as $account)
                    @include('livewire.dashboard.partials.account-row', ['account' => $account])
                @empty
                    <div
                        class="rounded-[1.5rem] border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-6 py-7 text-center shadow-[0_18px_40px_rgba(24,20,12,0.06)]">
                        <h3 class="font-[var(--font-display)] text-xl">No accounts imported yet</h3>
                        <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-[var(--color-muted)]">
                            Start with the legacy import format. The draft is persisted securely, so your latest input
                            stays available after refreshes.
                        </p>
                        <button type="button" wire:click="openImportModal"
                            class="mt-5 inline-flex items-center justify-center rounded-full bg-[var(--color-accent)] px-4 py-2 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_14px_32px_rgba(176,93,31,0.28)] transition hover:brightness-110">
                            Add first accounts
                        </button>
                    </div>
                @endforelse
            </div>
        </section>

        @include('livewire.dashboard.partials.activity-log-panel')
    </div>

    @include('livewire.dashboard.partials.program-settings-modal')
    @include('livewire.dashboard.partials.import-modal')
    @include('livewire.dashboard.partials.village-build-plan-modal')
</div>
