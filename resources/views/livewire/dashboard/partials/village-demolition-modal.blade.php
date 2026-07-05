@if ($showVillageDemolitionModal)
    @php
        $mainBuildingLevel = (int) ($demolitionSnapshot['main_building_level'] ?? 0);
        $canDemolish = $mainBuildingLevel >= 10;
        $activeDemolition = $demolitionSnapshot['active'] ?? null;
        $activeRecordedAt = is_array($activeDemolition) && !empty($activeDemolition['recorded_at'])
            ? \Carbon\CarbonImmutable::parse((string) $activeDemolition['recorded_at'])->getTimestamp()
            : now()->getTimestamp();
        $activeRemainingSeconds = is_array($activeDemolition) && isset($activeDemolition['remaining_seconds'])
            ? max(0, (int) $activeDemolition['remaining_seconds'] - max(0, now()->getTimestamp() - $activeRecordedAt))
            : null;
        $activeEndsAtTimestamp = $activeRemainingSeconds !== null
            ? (now()->getTimestamp() + $activeRemainingSeconds) * 1000
            : null;
        $formatClock = static function (int $seconds): string {
            return sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
        };
    @endphp

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-[var(--color-line)] px-5 py-3.5">
                <div>
                    <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Village demolition</p>
                    <h2 class="mt-0.5 font-[var(--font-display)] text-xl font-semibold">D - Delete</h2>
                    @if ($demolitionVillageLabel !== '')
                        <p class="mt-1 inline-flex rounded-md bg-[var(--color-panel-alt)] px-2 py-1 text-xs font-semibold text-[var(--color-muted)]">
                            {{ $demolitionVillageLabel }}
                        </p>
                    @endif
                </div>

                <button type="button" wire:click="closeVillageDemolitionModal"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden px-5 py-4">
                <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Main Building</p>
                            <p class="mt-1 text-sm font-semibold {{ $canDemolish ? 'text-emerald-800' : 'text-rose-800' }}">
                                Level {{ $mainBuildingLevel ?: 'unknown' }}
                            </p>
                        </div>

                        <button type="button" wire:click="refreshVillageDemolitionSnapshot" wire:loading.attr="disabled" wire:target="refreshVillageDemolitionSnapshot"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                            <span wire:loading.remove wire:target="refreshVillageDemolitionSnapshot">Refresh</span>
                            <span wire:loading wire:target="refreshVillageDemolitionSnapshot">Checking...</span>
                        </button>
                    </div>

                    @if (! $canDemolish)
                        <p class="mt-2 text-xs leading-5 text-rose-800">
                            Main Building level 10 is required before Travian allows demolition.
                        </p>
                    @endif
                </section>

                @if (is_array($activeDemolition))
                    <section class="rounded-lg border border-amber-500/35 bg-amber-500/10 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase text-amber-900">Active demolition</p>
                                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2 text-sm font-semibold text-amber-950"
                                    @if ($activeEndsAtTimestamp !== null) x-data="{
                                            endsAt: {{ $activeEndsAtTimestamp }},
                                            remaining: {{ $activeRemainingSeconds ?? 0 }},
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
                                        }" @endif>
                                    <span class="truncate">{{ $activeDemolition['name'] ?? 'Building' }}</span>
                                    @if (($activeDemolition['target_level'] ?? null) !== null)
                                        <span>to Lv {{ $activeDemolition['target_level'] }}</span>
                                    @endif
                                    @if ($activeRemainingSeconds !== null)
                                        <span class="rounded bg-white/60 px-2 py-0.5 font-mono text-xs" x-text="formatted()">
                                            {{ $formatClock($activeRemainingSeconds) }}
                                        </span>
                                    @elseif (!empty($activeDemolition['remaining_label']))
                                        <span class="rounded bg-white/60 px-2 py-0.5 font-mono text-xs">{{ $activeDemolition['remaining_label'] }}</span>
                                    @endif
                                    @if (!empty($activeDemolition['finish_label']))
                                        <span class="font-mono text-xs">Ends {{ $activeDemolition['finish_label'] }}</span>
                                    @endif
                                </div>
                            </div>

                            <button type="button" wire:click="queueCancelVillageDemolition"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-700/35 bg-white/80 text-sm font-black text-rose-800 transition hover:bg-rose-50"
                                title="Cancel demolition">
                                ×
                            </button>
                        </div>
                    </section>
                @endif

                <label class="grid gap-1 text-sm">
                    <span class="font-semibold text-[var(--color-ink)]">Building</span>
                    <select wire:model.change="demolitionSelectedSlotId"
                        class="h-10 min-w-0 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 text-sm outline-none focus:border-[var(--color-accent)]">
                        <option value="">Choose building</option>
                        @foreach ($demolitionBuildings as $building)
                            <option value="{{ $building['slot_id'] }}">
                                #{{ $building['slot_id'] }} {{ $building['name'] }} - Lv {{ $building['level'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                @if ($demolitionBuildings->isEmpty())
                    <p class="rounded-lg border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-4 py-5 text-center text-sm font-semibold text-[var(--color-muted)]">
                        No demolition list is saved yet. Refresh the Main Building snapshot.
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--color-line)] px-5 py-3.5">
                <button type="button" wire:click="closeVillageDemolitionModal"
                    class="rounded-lg border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Cancel
                </button>
                <button type="button" wire:click="queueVillageBuildingDemolition" wire:loading.attr="disabled" wire:target="queueVillageBuildingDemolition"
                    @disabled(! $canDemolish || $demolitionSelectedSlotId === null)
                    class="rounded-lg bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="queueVillageBuildingDemolition">Demolish one level</span>
                    <span wire:loading wire:target="queueVillageBuildingDemolition">Queuing...</span>
                </button>
            </div>
        </div>
    </div>
@endif
