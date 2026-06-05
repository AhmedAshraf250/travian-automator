<article
    class="overflow-hidden rounded-[1.35rem] border border-[var(--color-line)] bg-[var(--color-panel)] shadow-[0_14px_38px_rgba(24,20,12,0.08)]">
    <div class="flex flex-col gap-3 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 flex-1 space-y-3">
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" wire:click="toggleAccountExpansion({{ $account->id }})"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-base font-semibold text-[var(--color-accent)] transition hover:border-[var(--color-accent)]">
                    {{ $expandedAccounts[$account->id] ?? false ? '−' : '+' }}
                </button>

                <h3 class="text-base font-semibold text-[var(--color-ink)]">{{ $account->username }}</h3>

                <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $account->is_active ? 'bg-emerald-500/15 text-emerald-900' : 'bg-stone-800/10 text-stone-700' }}">
                    {{ $account->status->value }}
                </span>

                <span
                    class="rounded-full bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                    {{ $account->villages_count }} villages
                </span>

                <span
                    class="rounded-full bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                    {{ $account->last_sync_at?->diffForHumans() ?? 'Never synced' }}
                </span>

                <span class="truncate text-xs text-[var(--color-muted)]">{{ $account->server_url }}</span>
            </div>

            @php
                $resolvedUserAgent = $account->user_agent ?: ($globalDefaultUserAgent ?? null);
            @endphp

            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full bg-[var(--color-panel-alt)] px-3 py-1.5 text-[var(--color-ink)]">
                    Proxy: {{ $account->proxy_ip ? "{$account->proxy_ip}:{$account->proxy_port}" : 'Direct' }}
                </span>

                <span
                    class="inline-flex max-w-full items-center gap-2 rounded-full bg-[var(--color-panel-alt)] px-3 py-1.5 text-[var(--color-ink)] lg:max-w-[34rem]">
                    <span class="font-semibold text-[var(--color-muted)]">UA</span>
                    <span class="truncate">{{ $resolvedUserAgent ?: 'No user agent configured yet' }}</span>
                </span>

                @if (! $account->user_agent)
                    <span class="rounded-full bg-sky-500/10 px-3 py-1.5 text-sky-800">
                        Inherited from program settings when available
                    </span>
                @endif

                @if ($account->heroState)
                    <span class="rounded-full bg-violet-500/10 px-3 py-1.5 text-violet-900">
                        Hero {{ $account->heroState->status ?? 'unknown' }}
                        @if ($account->heroState->health_percent !== null)
                            · {{ (int) $account->heroState->health_percent }}%
                        @endif
                    </span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button" wire:click="openAccountSettingsModal({{ $account->id }})"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]"
                title="Account settings">
                ⚙
            </button>

            @if ($account->is_active)
                <button type="button" wire:click="pauseAccount({{ $account->id }})"
                    class="rounded-full border border-amber-800/20 bg-amber-500/10 px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-500/20">
                    Pause
                </button>
            @else
                <button type="button" wire:click="activateAccount({{ $account->id }})"
                    class="rounded-full border border-emerald-800/20 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-900 transition hover:bg-emerald-500/20">
                    Activate
                </button>
            @endif

            <button type="button" wire:click="requestAccountSync({{ $account->id }})"
                class="rounded-full border border-[var(--color-line-strong)] px-3 py-2 text-xs font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Update
            </button>
        </div>
    </div>

    @if ($expandedAccounts[$account->id] ?? false)
        <div class="border-t border-[var(--color-line)] bg-[var(--color-panel-alt)]/45 px-4 py-3">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-sm font-semibold text-[var(--color-ink)]">Villages</h4>
                </div>
            </div>

            <div class="space-y-2">
                @forelse ($account->villages as $village)
                    @include('livewire.dashboard.partials.village-row', ['account' => $account, 'village' => $village])
                @empty
                    <div
                        class="rounded-[1rem] border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-4 py-4 text-sm text-[var(--color-muted)]">
                        No villages are stored for this account yet. Once sync data arrives, each village will appear
                        here as a compact live row.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</article>
