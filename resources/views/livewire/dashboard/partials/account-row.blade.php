@php
    $resolvedUserAgent = $account->user_agent ?: ($globalDefaultUserAgent ?? null);
    $accountStatusValue = $account->is_active ? $account->status->value : 'paused';
    $accountStatusClasses = match ($accountStatusValue) {
        'syncing' => 'border-sky-500/30 bg-sky-500/10 text-sky-900',
        'connection_issue' => 'border-rose-500/35 bg-rose-500/10 text-rose-900',
        'error' => 'border-rose-500/30 bg-rose-500/10 text-rose-900',
        'paused' => 'border-amber-500/30 bg-amber-500/15 text-amber-950',
        default => $account->is_active
            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900'
            : 'border-amber-500/30 bg-amber-500/15 text-amber-950',
    };
    $accountAccentClass = $accountStatusValue === 'paused' || ! $account->is_active
        ? 'border-l-amber-400'
        : ($accountStatusValue === 'syncing' ? 'border-l-sky-400' : ($accountStatusValue === 'connection_issue' || $accountStatusValue === 'error' ? 'border-l-rose-500' : 'border-l-[var(--color-accent)]'));
    $accountNameClasses = match ($accountStatusValue) {
        'syncing' => 'border-sky-500/25 bg-sky-500/10 text-sky-950',
        'connection_issue', 'error' => 'border-rose-500/25 bg-rose-500/10 text-rose-950',
        'paused' => 'border-amber-500/25 bg-amber-500/10 text-amber-950',
        default => $account->is_active
            ? 'border-[var(--color-accent)]/20 bg-[var(--color-accent-soft)] text-[var(--color-ink)]'
            : 'border-amber-500/25 bg-amber-500/10 text-amber-950',
    };
    $accountTribeId = $account->villages
        ->map(fn ($village) => $village->runtimeState?->tribe_id)
        ->filter()
        ->first();
    $accountTribeLabel = match ((int) $accountTribeId) {
        1 => 'Roman',
        2 => 'Teuton',
        3 => 'Gaul',
        default => null,
    };
    $accountTribeIcon = match ((int) $accountTribeId) {
        1 => 'assets/troops-icons/ROMAN.png',
        2 => 'assets/troops-icons/TEUTON.png',
        3 => 'assets/troops-icons/GAUL.png',
        default => null,
    };
    $latestActivityAt = $account->latestTravianActivityLog?->executed_at ?? $account->latestTravianActivityLog?->created_at;
    $accountLastSeenAt = collect([$account->last_sync_at, $latestActivityAt])->filter()->max();
    $isWaitingForConnectionRetry = $account->isWaitingForConnectionRetry();
    $connectionRetryAtTimestamp = $account->connection_retry_after?->getTimestamp() * 1000;
@endphp

<article wire:key="account-row-{{ $account->id }}"
    class="overflow-visible rounded-lg border border-l-4 border-[var(--color-line)] {{ $accountAccentClass }} bg-[var(--color-panel)] shadow-[0_12px_28px_rgba(15,23,42,0.07)]">
    <div class="flex flex-col gap-3 border-b border-[var(--color-line)] bg-[var(--color-panel-alt)]/75 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="max-w-full truncate rounded-md border px-3 py-1.5 text-base font-semibold shadow-sm {{ $accountNameClasses }}">
                    {{ $account->username }}
                </h3>

                <span class="rounded-md border px-2.5 py-1 text-[11px] font-semibold {{ $accountStatusClasses }}">
                    {{ $accountStatusValue === 'connection_issue' ? 'connection' : $accountStatusValue }}
                </span>

                <span class="rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                    {{ $account->villages_count }} villages
                </span>

                <span class="rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                    Synced {{ $accountLastSeenAt?->diffForHumans() ?? 'never' }}
                </span>

                @if ($isWaitingForConnectionRetry)
                    <span
                        class="rounded-md border border-rose-500/25 bg-rose-500/10 px-2.5 py-1 text-[11px] font-semibold text-rose-950"
                        x-data="{
                            endsAt: {{ $connectionRetryAtTimestamp }},
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
                                    return 'Retrying...';
                                }

                                const minutes = Math.floor(this.remaining / 60);
                                const seconds = String(this.remaining % 60).padStart(2, '0');

                                return `Retry in ${minutes}:${seconds}`;
                            }
                        }"
                        x-text="label()">
                        Retry {{ $account->connection_retry_after?->diffForHumans() }}
                    </span>
                @endif

                @if ($accountTribeLabel !== null)
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                        @if ($accountTribeIcon !== null)
                            <img src="{{ asset($accountTribeIcon) }}" alt="{{ $accountTribeLabel }}"
                                class="h-4 w-4 shrink-0 object-contain"
                                onerror="this.classList.add('hidden')" />
                        @endif
                        {{ $accountTribeLabel }}
                    </span>
                @endif

                @if ($account->heroState)
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-violet-500/10 px-2.5 py-1 text-[11px] font-semibold text-violet-900"
                        title="Hero health {{ $account->heroState->health_percent !== null ? (int) $account->heroState->health_percent : '--' }}%, current status {{ $account->heroState->status ?? 'unknown' }}. Saved home village is the hero home, not necessarily his current position.">
                        <img src="{{ asset('assets/troops-icons/hero.png') }}" alt="Hero"
                            class="h-4 w-4 shrink-0 object-contain"
                            onerror="this.classList.add('hidden')" />
                        {{ $account->heroState->health_percent !== null ? (int) $account->heroState->health_percent : '--' }}%[{{ $account->heroState->status ?? 'unknown' }}]
                    </span>
                @endif
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2 text-xs text-[var(--color-muted)]">
                <span class="max-w-full truncate rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1">
                    {{ $account->server_url }}
                </span>
                <span class="rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1">
                    Proxy: {{ $account->proxy_ip ? (($account->proxy_scheme ?: 'http') . "://{$account->proxy_ip}:{$account->proxy_port}") : 'Direct' }}
                </span>
                <span class="max-w-full truncate rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 lg:max-w-[34rem]">
                    UA: {{ $resolvedUserAgent ?: 'Not set' }}
                </span>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button" wire:click="openAccountSettingsModal({{ $account->id }})"
                class="inline-flex items-center justify-center rounded-lg border border-[var(--color-line-strong)] px-3 py-2 text-xs font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Settings
            </button>

            @if ($account->is_active)
                <button type="button" wire:click="pauseAccount({{ $account->id }})"
                    class="rounded-lg border border-amber-800/20 bg-amber-500/10 px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-500/20">
                    Pause
                </button>
            @else
                <button type="button" wire:click="activateAccount({{ $account->id }})"
                    class="rounded-lg border border-emerald-800/20 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-900 transition hover:bg-emerald-500/20">
                    Activate
                </button>
            @endif

            <button type="button" wire:click="requestAccountSync({{ $account->id }})"
                class="rounded-lg border border-[var(--color-line-strong)] px-3 py-2 text-xs font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Update
            </button>
        </div>
    </div>

    <div class="bg-[var(--color-panel-alt)]/45 px-4 py-3">
        @if ($account->villages->isNotEmpty())
            <div class="relative space-y-2 border-l border-[var(--color-line-strong)] pl-4">
                @foreach ($account->villages as $village)
                    @include('livewire.dashboard.partials.village-row', ['account' => $account, 'village' => $village])
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-4 py-3 text-sm text-[var(--color-muted)]">
                No villages stored yet.
            </div>
        @endif
    </div>
</article>
