@if ($showActivityLog)
    <section class="relative flex h-full flex-col overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-[0_18px_55px_rgba(15,23,42,0.18)]">
        <div class="absolute inset-x-0 top-0 z-10 h-2 cursor-ns-resize bg-transparent hover:bg-[var(--color-accent)]/15"
            @pointerdown="startActivityLogResize($event)"
            title="Drag to resize activity log"
            aria-label="Drag to resize activity log"></div>

        <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-[var(--color-line)] bg-[var(--color-panel)] px-2.5 py-1.5">
            <div class="flex items-center gap-2">
                <h2 class="font-[var(--font-display)] text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Activity log</h2>
                <span class="rounded-md bg-[var(--color-panel-alt)] px-1.5 py-0.5 text-[10px] font-semibold text-[var(--color-muted)]">
                    {{ $activityLogCount }}
                </span>
            </div>

            <div class="flex items-center gap-1.5">
                <button type="button" wire:click="toggleActivityLog"
                    class="inline-flex h-6 items-center justify-center rounded-md border border-[var(--color-line-strong)] px-2 text-[11px] font-semibold text-[var(--color-ink)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Hide
                </button>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-auto overscroll-contain bg-[#f3f3f3] p-1 font-mono text-[11px] leading-4 text-slate-900">
            <div class="min-w-max divide-y divide-slate-300/80 border border-slate-300 bg-white">
                @forelse ($activityLogs as $activityLog)
                    @php
                        $activityType = $activityLog->activity_type->value;
                        $statusValue = $activityLog->status->value;
                        $payload = is_array($activityLog->payload) ? $activityLog->payload : [];
                        $result = is_array($activityLog->result) ? $activityLog->result : [];
                        $logOccurredAt = $activityLog->executed_at ?? $activityLog->created_at;
                        $logDisplayAt = $logOccurredAt?->timezone(config('app.timezone'));
                        $travianEvidenceKeys = [
                            'status_code',
                            'preview_status_code',
                            'confirm_status_code',
                            'reload_status_code',
                            'refresh_status_code',
                            'effective_uri',
                            'build_effective_uri',
                            'dorf1_effective_uri',
                            'dorf2_effective_uri',
                            'tasks_effective_uri',
                            'details_effective_uri',
                            'action_uri',
                        ];
                        $hasTravianEvidence = collect([$payload, $result])
                            ->contains(function (array $sourcePayload) use ($travianEvidenceKeys): bool {
                                foreach ($travianEvidenceKeys as $key) {
                                    if (array_key_exists($key, $sourcePayload) && filled($sourcePayload[$key])) {
                                        return true;
                                    }
                                }

                                return false;
                            });
                        $logSourceLabel = $hasTravianEvidence ? 'TRAVIAN' : 'APP';
                        $logSourceTitle = $hasTravianEvidence
                            ? 'Travian confirmed this activity.'
                            : 'Created by an automation rule or dashboard action.';
                        $logSourceClasses = $hasTravianEvidence
                            ? 'bg-blue-100 text-blue-950 ring-1 ring-blue-200'
                            : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';

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
                        $logFinishLabel = !empty($payload['finish_label'])
                            ? trim((string) $payload['finish_label'])
                            : null;
                        if ($logFinishLabel === '00:00') {
                            $logFinishLabel = null;
                        }
                        $rowClasses = match ($statusValue) {
                            'failed' => 'bg-rose-50',
                            'pending' => 'bg-amber-50',
                            'running' => 'bg-sky-50',
                            default => 'bg-white',
                        };
                        $statusClasses = $statusValue === 'failed'
                            ? 'bg-rose-100 text-rose-900 ring-1 ring-rose-200'
                            : ($statusValue === 'pending'
                                ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-200'
                                : ($statusValue === 'running'
                                    ? 'bg-sky-100 text-sky-900 ring-1 ring-sky-200'
                                    : 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200'));
                        $activityClasses = match ($activityType) {
                            'build' => 'bg-amber-100 text-amber-950 ring-1 ring-amber-200',
                            'sync' => 'bg-sky-100 text-sky-950 ring-1 ring-sky-200',
                            'hero' => 'bg-violet-100 text-violet-950 ring-1 ring-violet-200',
                            'transfer' => 'bg-emerald-100 text-emerald-950 ring-1 ring-emerald-200',
                            default => 'bg-slate-100 text-slate-800 ring-1 ring-slate-200',
                        };
                    @endphp

                    <div wire:key="activity-log-{{ $activityLog->id }}"
                        class="flex min-h-5 items-center gap-1.5 whitespace-nowrap px-1.5 py-0.5 {{ $rowClasses }}">
                        <span class="w-[8.7rem] shrink-0 text-slate-700">
                            {{ $logDisplayAt?->format('d/m/Y H:i:s') }}
                        </span>
                        <span class="text-slate-500">^</span>
                        <span class="rounded px-1 py-0 text-[10px] font-bold uppercase {{ $activityClasses }}">
                            {{ $activityType }}
                        </span>
                        <span class="rounded px-1 py-0 text-[10px] font-bold uppercase {{ $logSourceClasses }}"
                            title="{{ $logSourceTitle }}">
                            {{ $logSourceLabel }}
                        </span>
                        <span class="rounded px-1 py-0 text-[10px] font-bold uppercase {{ $statusClasses }}">
                            {{ $statusValue }}
                        </span>
                        <span class="font-semibold text-slate-950">
                            {{ $activityLog->message ?: ucfirst($activityType) }}
                        </span>

                        @if (!empty($payload['building_name']))
                            <span class="rounded bg-slate-100 px-1 py-0 font-semibold text-slate-800 ring-1 ring-slate-200">
                                {{ $payload['building_name'] }}@if (!empty($payload['target_level'])) Lv {{ $payload['target_level'] }}@endif
                            </span>
                        @endif

                        @if (!empty($payload['field_key']))
                            <span class="rounded bg-slate-100 px-1 py-0 font-semibold text-slate-800 ring-1 ring-slate-200">
                                {{ strtoupper((string) $payload['field_key']) }}
                            </span>
                        @endif

                        @if (!empty($payload['remaining_label']) || $logRemainingSeconds !== null)
                            <span
                                class="rounded bg-slate-100 px-1 py-0 font-semibold text-slate-700 ring-1 ring-slate-200"
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
                                x-text="`left ${formatted()}`" @endif>
                                @if ($logRemainingSeconds !== null)
                                    left {{ $logRemainingInitialClock }}
                                @else
                                    left {{ $payload['remaining_label'] }}
                                @endif
                            </span>
                        @endif

                        @if ($logFinishLabel !== null)
                            <span class="rounded bg-slate-100 px-1 py-0 font-semibold text-slate-700 ring-1 ring-slate-200">
                                Ends {{ $logFinishLabel }}
                            </span>
                        @endif

                        <span class="text-slate-500">
                            {{ $activityLog->account?->username ?? 'System' }}
                            @if ($activityLog->village)
                                | {{ $activityLog->village->name }}
                            @endif
                            | {{ $activityLog->executed_at?->diffForHumans() ?? $activityLog->created_at->diffForHumans() }}
                        </span>
                    </div>
                @empty
                    <div class="px-2 py-1 text-slate-500">
                        No activity yet.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@else
    <section class="ml-auto w-fit rounded-xl border border-[var(--color-accent)]/35 bg-[var(--color-panel)] p-1.5 shadow-[0_14px_40px_rgba(15,23,42,0.18)] ring-4 ring-[color:var(--color-accent-soft)]">
        <button type="button" wire:click="toggleActivityLog"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[var(--color-accent)] px-3.5 text-xs font-extrabold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-95"
            title="Show activity log">
            <span class="h-2 w-2 rounded-full bg-white/90 animate-pulse"></span>
            Show activity log
            <span class="rounded bg-white/20 px-1.5 py-0.5 text-[10px]">{{ $activityLogCount }}</span>
        </button>
    </section>
@endif
