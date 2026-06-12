@if ($showActivityLog)
    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-[var(--font-display)] text-lg font-semibold">Activity log</h2>
            </div>

            <div class="flex items-center gap-2">
                <span class="rounded-md bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                    {{ $activityLogs->count() }} rows
                </span>
                <button type="button" wire:click="toggleActivityLog"
                    class="inline-flex items-center justify-center rounded-lg border border-[var(--color-line-strong)] px-3 py-2 text-xs font-semibold text-[var(--color-ink)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Hide log
                </button>
            </div>
        </div>

        <div
            class="mt-4 max-h-[calc(100vh-16rem)] min-h-[18rem] overflow-y-auto overscroll-contain rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-2">
            <div class="space-y-2">
                @forelse ($activityLogs as $activityLog)
                    @php
                        $activityType = $activityLog->activity_type->value;
                        $statusValue = $activityLog->status->value;
                        $payload = is_array($activityLog->payload) ? $activityLog->payload : [];
                        $logRemainingSeconds = isset($payload['remaining_seconds'])
                            ? (int) $payload['remaining_seconds']
                            : null;
                        $logRemainingFromNow = $logRemainingSeconds !== null
                            ? max(0, $logRemainingSeconds - max(0, now()->getTimestamp() - ($activityLog->executed_at?->getTimestamp() ?? $activityLog->created_at->getTimestamp())))
                            : null;
                        $logEndsAtTimestamp = $logRemainingSeconds !== null
                            ? (($activityLog->executed_at?->getTimestamp() ?? $activityLog->created_at->getTimestamp()) + $logRemainingSeconds) * 1000
                            : null;
                        $logRemainingInitialClock = $logRemainingFromNow !== null
                            ? sprintf(
                                '%d:%02d:%02d',
                                intdiv($logRemainingFromNow, 3600),
                                intdiv($logRemainingFromNow % 3600, 60),
                                $logRemainingFromNow % 60,
                            )
                            : null;
                        $cardClasses = match ($activityType) {
                            'build' => 'border-amber-500/20 bg-amber-500/7',
                            'sync' => 'border-sky-500/20 bg-sky-500/7',
                            default => 'border-[var(--color-line)] bg-[var(--color-panel)]',
                        };
                        $statusClasses = $statusValue === 'failed'
                            ? 'bg-rose-500/15 text-rose-900'
                            : ($statusValue === 'pending'
                                ? 'bg-amber-500/12 text-amber-900'
                                : 'bg-emerald-500/10 text-emerald-900');
                    @endphp
                    <article wire:key="activity-log-{{ $activityLog->id }}"
                        class="rounded-lg border px-3 py-2.5 {{ $cardClasses }}">
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                            <span
                                class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                                {{ $activityType }}
                            </span>
                            <span class="rounded-md px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses }}">
                                {{ $statusValue }}
                            </span>
                            <span class="font-semibold text-[var(--color-ink)]">
                                {{ $activityLog->message ?: ucfirst($activityType) }}
                            </span>

                            @if (!empty($payload['building_name']))
                                <span
                                    class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-ink)]">
                                    {{ $payload['building_name'] }}
                                    @if (!empty($payload['target_level']))
                                        Lv {{ $payload['target_level'] }}
                                    @endif
                                </span>
                            @endif

                            @if (!empty($payload['field_key']))
                                <span
                                    class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-ink)]">
                                    {{ strtoupper((string) $payload['field_key']) }}
                                </span>
                            @endif

                            @if (!empty($payload['remaining_label']) || $logRemainingSeconds !== null)
                                <span
                                    class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-mono font-semibold text-[var(--color-muted)]"
                                    @if ($logRemainingSeconds !== null) x-data="{
                                            endsAt: {{ $logEndsAtTimestamp }},
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
                                            formatted() {
                                                const hours = String(Math.floor(this.remaining / 3600)).padStart(1, '0');
                                                const minutes = String(Math.floor((this.remaining % 3600) / 60)).padStart(2, '0');
                                                const seconds = String(this.remaining % 60).padStart(2, '0');

                                                return `${hours}:${minutes}:${seconds}`;
                                            }
                                        }"
                                    x-text="formatted()" @endif>
                                    @if ($logRemainingSeconds !== null)
                                        {{ $logRemainingInitialClock }}
                                    @else
                                        {{ $payload['remaining_label'] }}
                                    @endif
                                </span>
                            @endif

                            @if (!empty($payload['finish_label']))
                                <span
                                    class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-[11px] font-mono font-semibold text-[var(--color-muted)]">
                                    Ends {{ $payload['finish_label'] }}
                                </span>
                            @endif

                            <span class="text-[var(--color-muted)]">
                                {{ $activityLog->account?->username ?? 'System' }}
                            </span>

                            @if ($activityLog->village)
                                <span class="text-[var(--color-muted)]">· {{ $activityLog->village->name }}</span>
                            @endif

                            <span class="text-[var(--color-muted)]">
                                · {{ $activityLog->executed_at?->diffForHumans() ?? $activityLog->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </article>
                @empty
                    <div
                        class="rounded-lg border border-dashed border-[var(--color-line-strong)] px-4 py-5 text-sm text-[var(--color-muted)]">
                        No activity yet.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@else
    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-[var(--font-display)] text-lg font-semibold">Activity log</h2>
            </div>
            <button type="button" wire:click="toggleActivityLog"
                class="inline-flex items-center justify-center rounded-lg border border-[var(--color-line-strong)] px-3 py-2 text-xs font-semibold text-[var(--color-ink)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Show log
            </button>
        </div>
    </section>
@endif
