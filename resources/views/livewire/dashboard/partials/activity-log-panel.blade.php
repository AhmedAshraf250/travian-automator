@if ($showActivityLog)
    <section class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)] lg:sticky lg:bottom-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-[var(--font-display)] text-2xl">Activity log</h2>
                <p class="text-sm text-[var(--color-muted)]">The dashboard timeline for syncs, background jobs, build queues, and upcoming automation steps.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-[var(--color-panel-alt)] px-3 py-1 text-xs font-semibold text-[var(--color-muted)]">{{ $activityLogs->count() }} rows</span>
                <button type="button" wire:click="toggleActivityLog" class="inline-flex items-center justify-center rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold text-[var(--color-ink)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Hide log
                </button>
            </div>
        </div>

        <div class="mt-5 h-[24rem] overflow-y-scroll overscroll-contain rounded-[1.5rem] border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-3">
            <div class="space-y-3">
                @forelse ($activityLogs as $activityLog)
                    <article wire:key="activity-log-{{ $activityLog->id }}" class="rounded-[1.25rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $activityLog->message ?: ucfirst($activityLog->activity_type->value) }}</p>
                                <p class="mt-1 text-xs text-[var(--color-muted)]">
                                    {{ $activityLog->account?->username ?? 'System' }}
                                    @if ($activityLog->village)
                                        · {{ $activityLog->village->name }}
                                    @endif
                                </p>
                            </div>

                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] {{ $activityLog->status->value === 'failed' ? 'bg-rose-500/15 text-rose-900' : 'bg-stone-900/5 text-stone-700' }}">
                                {{ $activityLog->status->value }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-[var(--color-muted)]">
                            <span>{{ $activityLog->activity_type->value }}</span>
                            <span>{{ $activityLog->executed_at?->diffForHumans() ?? $activityLog->created_at->diffForHumans() }}</span>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1.25rem] border border-dashed border-[var(--color-line-strong)] px-4 py-5 text-sm text-[var(--color-muted)]">
                        Activity entries will appear here as import, sync, build, train, and manual actions are recorded.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@else
    <section class="rounded-[1.75rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-5 shadow-[0_18px_50px_rgba(24,20,12,0.1)]">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-[var(--font-display)] text-2xl">Activity log</h2>
                <p class="text-sm text-[var(--color-muted)]">The log block is hidden, but still reserved as an independent area in the layout.</p>
            </div>
            <button type="button" wire:click="toggleActivityLog" class="inline-flex items-center justify-center rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold text-[var(--color-ink)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Show log
            </button>
        </div>
    </section>
@endif
