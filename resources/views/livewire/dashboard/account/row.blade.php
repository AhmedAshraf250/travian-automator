@php
    $resolvedUserAgent = $account->user_agent ?: ($globalDefaultUserAgent ?? null);
    $programPaused = ! (bool) ($automationEnabled ?? true);
    $accountStatusValue = $programPaused && $account->is_active ? 'program_paused' : ($account->is_active ? $account->status->value : 'paused');
    $accountStatusClasses = match ($accountStatusValue) {
        'syncing' => 'border-sky-500/30 bg-sky-500/10 text-sky-900',
        'connection_issue' => 'border-rose-500/35 bg-rose-500/10 text-rose-900',
        'error' => 'border-rose-500/30 bg-rose-500/10 text-rose-900',
        'paused', 'program_paused' => 'border-amber-500/30 bg-amber-500/15 text-amber-950',
        default => $account->is_active
            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900'
            : 'border-amber-500/30 bg-amber-500/15 text-amber-950',
    };
    $accountAccentClass = $accountStatusValue === 'paused' || $accountStatusValue === 'program_paused' || ! $account->is_active
        ? 'border-l-amber-400'
        : ($accountStatusValue === 'syncing' ? 'border-l-sky-400' : ($accountStatusValue === 'connection_issue' || $accountStatusValue === 'error' ? 'border-l-rose-500' : 'border-l-[var(--color-accent)]'));
    $accountHeaderClasses = match ($accountStatusValue) {
        'syncing' => 'border-sky-500/25 bg-sky-100/90 text-sky-950',
        'connection_issue', 'error' => 'border-rose-500/35 bg-rose-100/90 text-rose-950',
        'paused', 'program_paused' => 'border-amber-500/45 bg-amber-100 text-amber-950',
        default => $account->is_active
            ? 'border-teal-600/20 bg-teal-50/95 text-slate-900'
            : 'border-amber-500/45 bg-amber-100 text-amber-950',
    };
    $accountNameClasses = match ($accountStatusValue) {
        'syncing' => 'border-sky-500/25 bg-sky-500/10 text-sky-950',
        'connection_issue', 'error' => 'border-rose-500/25 bg-rose-500/10 text-rose-950',
        'paused', 'program_paused' => 'border-amber-500/25 bg-amber-500/10 text-amber-950',
        default => $account->is_active
            ? 'border-[var(--color-accent)]/20 bg-[var(--color-accent-soft)] text-[var(--color-ink)]'
            : 'border-amber-500/25 bg-amber-500/10 text-amber-950',
    };
    $accountTribeId = $loadedVillages
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
    $activeProxyLabel = $account->activeProxy
        ? $account->activeProxy->endpointLabel()
        : ($account->proxy_ip ? (($account->proxy_scheme ?: 'http') . "://{$account->proxy_ip}:{$account->proxy_port}") : 'Direct');
    $proxyPoolCount = $account->proxies->count();
    $readyProxyCount = $account->proxies->filter(fn ($proxy) => $proxy->status === \App\Models\AccountProxy::StatusActive && $proxy->isAvailable())->count();
    $coolingProxyCount = $account->proxies->where('status', \App\Models\AccountProxy::StatusCooldown)->count();
    $proxySummary = $proxyPoolCount > 0
        ? "Proxy pool: {$readyProxyCount}/{$proxyPoolCount} ready"
            .($coolingProxyCount > 0 ? " · {$coolingProxyCount} cooling" : '')
            ." · using {$activeProxyLabel}"
        : "Proxy: {$activeProxyLabel}";
@endphp

<article wire:key="account-row-{{ $account->id }}"
    class="overflow-visible rounded-lg border border-l-4 border-[var(--color-line)] {{ $accountAccentClass }} bg-slate-50/75 shadow-[0_8px_22px_rgba(15,23,42,0.06)]">
    <div class="flex flex-col gap-2 rounded-t-md border-b-2 px-3 py-2 shadow-[inset_0_-1px_0_rgba(255,255,255,0.65)] lg:flex-row lg:items-center lg:justify-between {{ $accountHeaderClasses }}">
        <div class="min-w-0 space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="inline-flex h-8 max-w-full items-center truncate rounded-md border px-3 text-base font-semibold shadow-sm {{ $accountNameClasses }}">
                    {{ $account->username }}
                </h3>

                <span class="inline-flex h-7 items-center rounded-md border px-2.5 text-[11px] font-semibold {{ $accountStatusClasses }}">
                    {{ $accountStatusValue === 'connection_issue' ? 'connection' : ($accountStatusValue === 'program_paused' ? 'program paused' : $accountStatusValue) }}
                </span>

                <span class="inline-flex h-7 items-center rounded-md bg-white/55 px-2.5 text-[11px] font-semibold {{ str_contains($accountHeaderClasses, 'text-amber') ? 'text-amber-950/75' : 'text-slate-700' }}">
                    {{ $account->villages_count }} villages
                </span>

                <span class="inline-flex h-7 items-center rounded-md bg-white/55 px-2.5 text-[11px] font-semibold {{ str_contains($accountHeaderClasses, 'text-amber') ? 'text-amber-950/75' : 'text-slate-700' }}">
                    Synced {{ $accountLastSeenAt?->diffForHumans() ?? 'never' }}
                </span>

                @if ($isWaitingForConnectionRetry)
                    <span
                        class="inline-flex h-7 items-center rounded-md border border-rose-500/25 bg-rose-500/10 px-2.5 text-[11px] font-semibold text-rose-950"
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
                    <span class="inline-flex h-7 items-center gap-1.5 rounded-md bg-white/55 px-2.5 text-[11px] font-semibold {{ str_contains($accountHeaderClasses, 'text-amber') ? 'text-amber-950/75' : 'text-slate-700' }}">
                        @if ($accountTribeIcon !== null)
                            <img src="{{ asset($accountTribeIcon) }}" alt="{{ $accountTribeLabel }}"
                                class="h-4 w-4 shrink-0 object-contain"
                                onerror="this.classList.add('hidden')" />
                        @endif
                        {{ $accountTribeLabel }}
                    </span>
                @endif

                @if ($account->heroState)
                    <span class="inline-flex h-7 items-center gap-1.5 rounded-md bg-violet-500/10 px-2.5 text-[11px] font-semibold text-violet-900"
                        title="Hero health {{ $account->heroState->health_percent !== null ? (int) $account->heroState->health_percent : '--' }}%, current status {{ $account->heroState->status ?? 'unknown' }}. Saved home village is the hero home, not necessarily his current position.">
                        <img src="{{ asset('assets/troops-icons/hero.png') }}" alt="Hero"
                            class="h-4 w-4 shrink-0 object-contain"
                            onerror="this.classList.add('hidden')" />
                        {{ $account->heroState->health_percent !== null ? (int) $account->heroState->health_percent : '--' }}%[{{ $account->heroState->status ?? 'unknown' }}]
                    </span>
                @endif
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-1.5 text-[11px] {{ str_contains($accountHeaderClasses, 'text-amber') ? 'text-amber-950/70' : 'text-slate-600' }}">
                <span class="inline-flex h-6 max-w-full items-center truncate rounded-md bg-white/55 px-2.5">
                    {{ $account->server_url }}
                </span>
                <span class="inline-flex h-6 max-w-full items-center truncate rounded-md bg-white/55 px-2.5">
                    {{ $proxySummary }}
                </span>
                <span class="inline-flex h-6 max-w-full items-center truncate rounded-md bg-white/55 px-2.5 lg:max-w-[34rem]">
                    UA: {{ $resolvedUserAgent ?: 'Not set' }}
                </span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)]/80 p-1 shadow-sm"
            title="Account level actions">
            <span class="inline-flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-[var(--color-panel-alt)] ring-1 ring-black/5">
                <img src="{{ asset('assets/TRAVIAN/dataset-card.png') }}" alt="Account"
                    class="h-7 w-7 object-cover"
                    onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" />
                <span class="hidden text-[10px] font-black uppercase text-[var(--color-muted)]">A</span>
            </span>
            <button type="button" wire:click="$dispatch('dashboard-toggle-account-expansion', { accountId: {{ $account->id }} })"
                class="inline-flex h-8 min-w-10 items-center justify-center gap-1 rounded-md border px-2 text-xs font-extrabold shadow-sm transition {{ $accountIsExpanded ? 'border-sky-700/25 bg-sky-500/15 text-sky-950 hover:bg-sky-500/25' : 'border-slate-500/25 bg-slate-100 text-slate-800 hover:bg-slate-200' }}"
                title="{{ $accountIsExpanded ? 'Hide village rows' : 'Show village rows' }}"
                aria-label="{{ $accountIsExpanded ? 'Hide village rows' : 'Show village rows' }}">
                <span class="text-sm leading-none">{{ $accountIsExpanded ? '▾' : '▸' }}</span>
                <span>{{ $account->villages_count }}</span>
            </button>
            <button type="button" wire:click="$dispatch('dashboard-open-account-settings', { accountId: {{ $account->id }} })"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[var(--color-line-strong)] text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]"
                title="Account settings"
                aria-label="Account settings">
                &#9881;
            </button>

            @if ($account->is_active)
                <button type="button" wire:click="pauseAccount({{ $account->id }})"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-700/35 bg-amber-400/35 text-sm font-black text-amber-950 shadow-sm transition hover:bg-amber-400/55"
                    title="Pause this account"
                    aria-label="Pause this account">
                    &#9208;
                </button>
            @else
                <button type="button" wire:click="activateAccount({{ $account->id }})"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-700/35 bg-emerald-500/25 text-sm font-black text-emerald-950 shadow-sm transition hover:bg-emerald-500/40"
                    title="Activate this account"
                    aria-label="Activate this account">
                    &#9654;
                </button>
            @endif

            <button type="button" wire:click="requestAccountSync({{ $account->id }})" wire:loading.attr="disabled" wire:target="requestAccountSync({{ $account->id }})"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[var(--color-line-strong)] text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] disabled:cursor-wait disabled:opacity-60"
                title="Update account and run automation"
                aria-label="Update account and run automation">
                &#8635;
            </button>
        </div>
    </div>

    @if ($accountIsExpanded)
        <div class="border-t border-white/70 bg-[var(--color-panel-alt)]/55 px-4 pb-3 pt-4">
        @if ($loadedVillages->isNotEmpty())
            <div class="relative space-y-2 border-l-2 border-[var(--color-line-strong)] pl-4">
                @foreach ($loadedVillages as $village)
                    <livewire:dashboard.village.row
                        :village-id="$village->id"
                        :global-field-priority="$globalFieldPriority"
                        :global-field-level-cap="$globalFieldLevelCap"
                        :global-prioritize-crop-fields-when-negative="$globalPrioritizeCropFieldsWhenNegative"
                        :dashboard-revision="$dashboardRevision"
                        :key="'dashboard-village-row-'.$village->id" />
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-4 py-3 text-sm text-[var(--color-muted)]">
                No villages stored yet.
            </div>
        @endif
        </div>
    @endif
</article>
