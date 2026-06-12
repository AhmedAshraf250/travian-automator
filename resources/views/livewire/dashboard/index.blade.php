@php
    $shouldPollDashboard = ! $showProgramSettingsModal && ! $showAccountSettingsModal && ! $showImportModal && ! $showVillageBuildPlanModal;
@endphp

<div class="min-h-screen">
    <div class="mx-auto flex min-h-screen w-full max-w-[118rem] flex-col gap-4 px-3 py-3 sm:px-4 lg:px-5"
        @if ($shouldPollDashboard) wire:poll.10s.keep-alive="refreshDashboardIfChanged" @endif>
        <header class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-4 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-[var(--font-display)] text-xl font-semibold text-[var(--color-ink)] sm:text-2xl">
                            Travian Multi-Account Automation
                        </h1>
                        <span
                            class="inline-flex items-center gap-2 rounded-md border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-800">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Smart DB refresh
                        </span>
                        <span
                            class="rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                            10s DB check
                        </span>
                    </div>

                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Accounts</p>
                            <p class="text-xl font-semibold">{{ $stats['accounts'] }}</p>
                        </div>
                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Active</p>
                            <p class="text-xl font-semibold">{{ $stats['activeAccounts'] }}</p>
                        </div>
                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Villages</p>
                            <p class="text-xl font-semibold">{{ $stats['villages'] }}</p>
                        </div>
                        <div class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Syncing</p>
                            <p class="text-xl font-semibold">{{ $stats['syncing'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="$refresh"
                        class="inline-flex items-center justify-center rounded-lg border border-[var(--color-line-strong)] px-3 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                        Refresh
                    </button>
                    <span
                        class="inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold {{ ($automationEnabled ?? true) ? 'border-emerald-700/20 bg-emerald-500/10 text-emerald-900' : 'border-amber-700/20 bg-amber-500/10 text-amber-900' }}">
                        Build &amp; Automation {{ ($automationEnabled ?? true) ? 'ON' : 'OFF' }}
                    </span>
                    <button type="button" wire:click="toggleAutomation"
                        class="inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition {{ ($automationEnabled ?? true) ? 'border-amber-700/20 bg-amber-500/10 text-amber-900 hover:bg-amber-500/20' : 'border-emerald-700/20 bg-emerald-500/10 text-emerald-900 hover:bg-emerald-500/20' }}">
                        {{ ($automationEnabled ?? true) ? 'Pause' : 'Resume' }}
                    </button>
                    <button type="button" wire:click="openProgramSettingsModal"
                        class="inline-flex items-center justify-center rounded-lg border border-[var(--color-line-strong)] px-3 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                        Program settings
                    </button>
                    <button type="button" wire:click="openImportModal"
                        class="inline-flex items-center justify-center rounded-lg bg-[var(--color-accent)] px-3 py-2 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                        Import accounts
                    </button>
                </div>
            </div>
        </header>

        @if (session('dashboard-banner'))
            <div class="rounded-lg border border-emerald-700/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900">
                {{ session('dashboard-banner') }}
            </div>
        @endif

        <div class="grid min-h-0 gap-4 2xl:grid-cols-[minmax(0,1fr)_26rem]">
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
                                class="mt-4 inline-flex items-center justify-center rounded-lg bg-[var(--color-accent)] px-4 py-2 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                                Add first accounts
                            </button>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="min-w-0 2xl:sticky 2xl:top-4 2xl:self-start">
                @include('livewire.dashboard.partials.activity-log-panel')
            </aside>
        </div>
    </div>

    @include('livewire.dashboard.partials.program-settings-modal')
    @include('livewire.dashboard.partials.account-settings-modal')
    @include('livewire.dashboard.partials.import-modal')
    @include('livewire.dashboard.partials.village-build-plan-modal')
</div>
