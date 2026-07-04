@php
    $shouldPollDashboard = ! $showProgramSettingsModal && ! $showAccountSettingsModal && ! $showImportModal && ! $showVillageBuildPlanModal && ! $showMarketplaceTransferModal && ! $showVillageDemolitionModal;
    $programIsRunning = (bool) ($automationEnabled ?? true);
@endphp

<div class="min-h-screen">
    <div class="mx-auto flex min-h-screen w-full max-w-[118rem] flex-col gap-4 px-3 py-3 sm:px-4 lg:px-5"
        style="{{ $showActivityLog ? 'padding-bottom: calc('.$activityLogHeight.'vh + 1.25rem);' : 'padding-bottom: 4rem;' }}"
        @if ($shouldPollDashboard) wire:poll.10s.keep-alive="refreshDashboardIfChanged" @endif>
        <header class="sticky top-0 z-40 -mx-3 border-b px-3 py-2 shadow-[0_10px_28px_rgba(15,23,42,0.08)] backdrop-blur sm:-mx-4 sm:px-4 lg:-mx-5 lg:px-5 {{ $programIsRunning ? 'border-emerald-500/20 bg-[var(--color-panel)]/95' : 'border-amber-400 bg-amber-50/95' }}">
            <div class="mx-auto flex max-w-[118rem] flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <h1 class="font-[var(--font-display)] text-lg font-semibold sm:text-xl {{ $programIsRunning ? 'text-emerald-800' : 'text-amber-950' }}">
                        Travian Multi-Account Automation
                    </h1>

                    <span
                        class="inline-flex items-center gap-2 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $programIsRunning ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900' : 'border-amber-500/35 bg-amber-300/35 text-amber-950' }}"
                        title="Global build and automation state">
                        <span class="relative inline-flex h-5 w-9 items-center rounded-full {{ $programIsRunning ? 'bg-emerald-500' : 'bg-amber-400' }}">
                            <span class="absolute inline-flex h-4 w-4 items-center justify-center rounded-full bg-white text-[9px] font-black shadow-sm transition {{ $programIsRunning ? 'right-0.5 text-emerald-700' : 'left-0.5 text-amber-800' }}">
                                {{ $programIsRunning ? '✓' : '×' }}
                            </span>
                        </span>
                        {{ $programIsRunning ? 'Running' : 'Paused' }}
                    </span>

                    <span
                        class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]"
                        title="Dashboard checks local database changes every 10 seconds">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        DB 10s
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @foreach ([
                        'Accounts' => $stats['accounts'],
                        'Active' => $stats['activeAccounts'],
                        'Villages' => $stats['villages'],
                        'Syncing' => $stats['syncing'],
                    ] as $statLabel => $statValue)
                        <span class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2.5 text-xs font-semibold text-[var(--color-muted)]">
                            {{ $statLabel }}
                            <strong class="text-sm text-[var(--color-ink)]">{{ $statValue }}</strong>
                        </span>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="toggleAutomation"
                        class="inline-flex h-9 items-center justify-center rounded-lg border px-3 text-sm font-semibold transition {{ $programIsRunning ? 'border-amber-700/25 bg-amber-500/10 text-amber-900 hover:bg-amber-500/20' : 'border-emerald-700/25 bg-emerald-500/10 text-emerald-900 hover:bg-emerald-500/20' }}"
                        title="{{ $programIsRunning ? 'Pause all automation' : 'Resume all automation' }}">
                        {{ $programIsRunning ? 'Pause' : 'Resume' }}
                    </button>
                    <button type="button" wire:click="openProgramSettingsModal"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] px-3 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                        Program settings
                    </button>
                    <button type="button" wire:click="openImportModal"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 text-sm font-semibold text-[var(--color-ink)] shadow-sm transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]"
                        title="Add account credentials and queue login checks">
                        <span class="text-base leading-none text-[var(--color-accent)]">⇥</span>
                        Accounts &amp; Login
                    </button>
                </div>
            </div>
        </header>

        @if (session('dashboard-banner'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5200)" x-show="show" x-transition.opacity.duration.200ms x-cloak
                class="fixed right-3 top-16 z-50 flex max-w-md items-start gap-3 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-3 text-sm text-[var(--color-ink)] shadow-[0_18px_55px_rgba(15,23,42,0.18)] sm:right-5"
                role="status">
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $programIsRunning ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                <p class="min-w-0 flex-1 leading-5">{{ session('dashboard-banner') }}</p>
                <button type="button" @click="show = false"
                    class="-mr-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-sm font-bold text-[var(--color-muted)] hover:bg-[var(--color-panel-alt)]"
                    aria-label="Dismiss notification">
                    ×
                </button>
            </div>
        @endif

        <section class="min-w-0 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-[var(--font-display)] text-lg font-semibold">Accounts</h2>
                <span class="rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                    {{ $accounts->count() }} rows
                </span>
            </div>

            <div class="space-y-3">
                @forelse ($accounts as $account)
                    @include('livewire.dashboard.partials.account-row', ['account' => $account])
                @empty
                    <div class="rounded-lg border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-5 py-6 text-center shadow-sm">
                        <h3 class="font-[var(--font-display)] text-lg font-semibold">No accounts imported yet</h3>
                        <button type="button" wire:click="openImportModal"
                            class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-4 py-2 text-sm font-semibold text-[var(--color-ink)] shadow-sm transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                            <span class="text-base leading-none text-[var(--color-accent)]">⇥</span>
                            Accounts &amp; Login
                        </button>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="pointer-events-none fixed inset-x-0 bottom-0 z-30 px-3 pb-3 sm:px-4 lg:px-5">
        <div class="pointer-events-auto mx-auto max-w-[96rem]">
            @if ($showActivityLog)
                <div style="height: {{ $activityLogHeight }}vh; min-height: 11rem; max-height: 38rem;">
                    @include('livewire.dashboard.partials.activity-log-panel')
                </div>
            @else
                @include('livewire.dashboard.partials.activity-log-panel')
            @endif
        </div>
    </aside>

    @include('livewire.dashboard.partials.program-settings-modal')
    @include('livewire.dashboard.partials.account-settings-modal')
    @include('livewire.dashboard.partials.import-modal')
    @include('livewire.dashboard.partials.village-build-plan-modal')
    @include('livewire.dashboard.partials.marketplace-transfer-modal')
    @include('livewire.dashboard.partials.village-demolition-modal')
</div>
