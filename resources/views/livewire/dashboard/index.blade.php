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
                    @include('livewire.dashboard.partials.account-row', ['account' => $account])
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

        @include('livewire.dashboard.partials.activity-log-panel')
    </div>

    @include('livewire.dashboard.partials.import-modal')
</div>
