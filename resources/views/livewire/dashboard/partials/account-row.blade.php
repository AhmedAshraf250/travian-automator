<article class="overflow-hidden rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
    <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="toggleAccountExpansion({{ $account->id }})"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-accent)] transition hover:border-[var(--color-accent)]"
                >
                    {{ $expandedAccounts[$account->id] ?? false ? '−' : '+' }}
                </button>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold">{{ $account->username }}</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-500/15 text-emerald-900' : 'bg-stone-800/10 text-stone-700' }}">
                            {{ $account->status->value }}
                        </span>
                    </div>

                    <p class="text-sm text-[var(--color-muted)]">{{ $account->server_url }}</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">Proxy</p>
                    <p class="mt-2 text-sm font-medium text-[var(--color-ink)]">
                        {{ $account->proxy_ip ? "{$account->proxy_ip}:{$account->proxy_port}" : 'Direct connection' }}
                    </p>
                </div>

                <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">User agent</p>
                    <p class="mt-2 line-clamp-2 text-sm font-medium text-[var(--color-ink)]">
                        {{ $account->user_agent ?: 'Not assigned yet' }}
                    </p>
                </div>

                <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">Villages</p>
                    <p class="mt-2 text-sm font-medium text-[var(--color-ink)]">{{ $account->villages_count }}</p>
                </div>

                <div class="rounded-2xl bg-[var(--color-panel-alt)] px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">Last sync</p>
                    <p class="mt-2 text-sm font-medium text-[var(--color-ink)]">{{ $account->last_sync_at?->diffForHumans() ?? 'Never synced' }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:max-w-xl lg:justify-end">
            @if ($account->is_active)
                <button type="button" wire:click="pauseAccount({{ $account->id }})" class="rounded-full border border-amber-800/20 bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-900 transition hover:bg-amber-500/20">Pause</button>
            @else
                <button type="button" wire:click="activateAccount({{ $account->id }})" class="rounded-full border border-emerald-800/20 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-500/20">Activate</button>
            @endif

            <button type="button" wire:click="requestAccountSync({{ $account->id }})" class="rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">Update now</button>
            <button type="button" class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Village settings</button>
            <button type="button" class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Train troops</button>
            <button type="button" class="rounded-full border border-[var(--color-line)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">Send resources</button>
        </div>
    </div>

    @if ($expandedAccounts[$account->id] ?? false)
        <div class="border-t border-[var(--color-line)] bg-[var(--color-panel-alt)]/50 px-5 py-4">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h4 class="font-semibold">Villages</h4>
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--color-muted)]">Resource flow, storage, troops, and runtime state</p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($account->villages as $village)
                    @include('livewire.dashboard.partials.village-row', ['account' => $account, 'village' => $village])
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-4 py-5 text-sm text-[var(--color-muted)]">
                        No villages are stored for this account yet. Once the sync layer is connected, villages and their resource states will appear here.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</article>
