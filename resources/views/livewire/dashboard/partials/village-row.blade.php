@php
    $resourceState = $village->resourceState;
    $runtimeState = $village->runtimeState;
    $settings = $village->settings;
    $coordinateText = $village->x !== null && $village->y !== null ? "[{$village->x}|{$village->y}]" : '[--|--]';
    $populationText = $village->population > 0 ? (string) $village->population : '--';
    $troopSlots = $runtimeState?->normalizedTroopSlots() ?? array_fill(0, 11, 0);
    $troopSlots = array_slice(array_pad($troopSlots, 11, 0), 0, 11);
    $troopVector = '[' . implode(',', $troopSlots) . ']';
    $incomingAttackCount = (int) ($runtimeState?->incoming_attack_count ?? 0);
    $incomingReinforcementCount = (int) ($runtimeState?->incoming_reinforcement_count ?? 0);
    $outgoingMovementCount = (int) ($runtimeState?->outgoing_movement_count ?? 0);
    $movementEntries = collect($runtimeState?->movement_entries ?? []);
    $constructionEntries = collect($runtimeState?->construction_entries ?? [])->take(3);
    $remainingConstructionCount = max(0, collect($runtimeState?->construction_entries ?? [])->count() - $constructionEntries->count());
    $serverReportedAtTimestamp = $runtimeState?->server_reported_at?->getTimestamp() ?? now()->getTimestamp();
    $formatClock = static function (?int $seconds): ?string {
        if ($seconds === null) {
            return null;
        }

        $seconds = max(0, $seconds);

        return sprintf(
            '%d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60,
        );
    };
    $statusDotClasses = $village->is_active ? 'bg-emerald-500' : 'bg-amber-500';
    $fieldsAutomationEnabled = ! ($settings?->pause_fields ?? false);
    $buildingsAutomationEnabled = ! ($settings?->pause_buildings ?? false);
    $resourceCards = [
        [
            'short' => 'W',
            'value' => $resourceState?->wood ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'production' => $resourceState?->wood_production ?? 0,
        ],
        [
            'short' => 'C',
            'value' => $resourceState?->clay ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'production' => $resourceState?->clay_production ?? 0,
        ],
        [
            'short' => 'I',
            'value' => $resourceState?->iron ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'production' => $resourceState?->iron_production ?? 0,
        ],
        [
            'short' => 'G',
            'value' => $resourceState?->crop ?? 0,
            'capacity' => $resourceState?->granary_capacity ?? 0,
            'production' => $resourceState?->crop_production ?? 0,
        ],
    ];
@endphp

<div wire:key="village-row-{{ $village->id }}"
    class="rounded-[1rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-3 py-2.5 shadow-[0_10px_24px_rgba(24,20,12,0.05)]">
    <div class="grid gap-2.5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-[var(--color-ink)]">
                <span class="inline-flex items-center justify-center" title="{{ $village->is_active ? 'Enabled' : 'Paused' }}">
                    <span class="h-3 w-3 rounded-full {{ $statusDotClasses }}"></span>
                </span>

                <span class="font-semibold">{{ $village->name }}</span>
                <span class="text-[var(--color-muted)]">{{ $coordinateText }}</span>

                <span class="rounded-full bg-[var(--color-panel-alt)] px-2.5 py-1 font-semibold text-[var(--color-muted)]">
                    Pop {{ $populationText }}
                </span>

                <span
                    class="rounded-full {{ $fieldsAutomationEnabled ? 'bg-emerald-500/10 text-emerald-800' : 'bg-stone-800/8 text-stone-700' }} px-2.5 py-1 font-semibold">
                    Fields {{ $fieldsAutomationEnabled ? 'ON' : 'OFF' }}
                </span>

                <span
                    class="rounded-full {{ $buildingsAutomationEnabled ? 'bg-sky-500/10 text-sky-800' : 'bg-stone-800/8 text-stone-700' }} px-2.5 py-1 font-semibold">
                    Buildings {{ $buildingsAutomationEnabled ? 'ON' : 'OFF' }}
                </span>

                <span class="rounded-full bg-[var(--color-panel-alt)] px-2.5 py-1 font-mono font-semibold text-[var(--color-muted)]">
                    {{ $troopVector }}
                </span>

                <span class="text-[var(--color-line-strong)]">|</span>

                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach ($resourceCards as $resourceCard)
                        <span
                            class="whitespace-nowrap rounded-full border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-2.5 py-1 font-mono font-semibold text-[var(--color-ink)]">
                            {{ $resourceCard['short'] }} {{ $resourceCard['value'] }}/{{ $resourceCard['capacity'] }}
                            +{{ $resourceCard['production'] }}/h
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] text-[var(--color-ink)]">
                @forelse ($constructionEntries as $constructionEntry)
                    @php
                        $constructionRemainingSeconds = isset($constructionEntry['remaining_seconds'])
                            ? (int) $constructionEntry['remaining_seconds']
                            : null;
                        $constructionInitialClock = $formatClock(
                            $constructionRemainingSeconds !== null
                                ? max(0, $constructionRemainingSeconds - max(0, now()->getTimestamp() - $serverReportedAtTimestamp))
                                : null,
                        );
                    @endphp
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-2.5 py-1">
                        <span class="font-semibold">
                            {{ $constructionEntry['building_name'] ?? 'Building' }}
                        </span>
                        @if (array_key_exists('target_level', $constructionEntry) && $constructionEntry['target_level'] !== null)
                            <span class="font-semibold text-[var(--color-accent)]">
                                Lv {{ $constructionEntry['target_level'] }}
                            </span>
                        @endif
                        @if (!empty($constructionEntry['remaining_label']) || $constructionRemainingSeconds !== null)
                            <span class="font-mono text-[11px] text-[var(--color-muted)]"
                                @if ($constructionRemainingSeconds !== null) x-data="{
                                        remaining: Math.max(0, {{ max(0, $constructionRemainingSeconds - max(0, now()->getTimestamp() - $serverReportedAtTimestamp)) }}),
                                        init() {
                                            setInterval(() => {
                                                if (this.remaining > 0) {
                                                    this.remaining--;
                                                }
                                            }, 1000);
                                        },
                                        formatted() {
                                            const hours = String(Math.floor(this.remaining / 3600)).padStart(1, '0');
                                            const minutes = String(Math.floor((this.remaining % 3600) / 60)).padStart(2, '0');
                                            const seconds = String(this.remaining % 60).padStart(2, '0');

                                            return `${hours}:${minutes}:${seconds}`;
                                        }
                                    }"
                                x-text="formatted()" @endif>
                                @if ($constructionRemainingSeconds !== null)
                                    {{ $constructionInitialClock }}
                                @else
                                    {{ $constructionEntry['remaining_label'] }}
                                @endif
                            </span>
                        @endif
                        @if (!empty($constructionEntry['finish_label']))
                            <span class="font-mono text-[11px] text-[var(--color-muted)]">
                                Ends {{ $constructionEntry['finish_label'] }}
                            </span>
                        @endif
                    </span>
                @empty
                    <span
                        class="rounded-full border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                        No active construction
                    </span>
                @endforelse

                @if ($remainingConstructionCount > 0)
                    <span
                        class="rounded-full border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                        +{{ $remainingConstructionCount }} more
                    </span>
                @endif

                @if ($movementEntries->isNotEmpty() || $incomingAttackCount > 0 || $incomingReinforcementCount > 0 || $outgoingMovementCount > 0)
                    <span class="text-[var(--color-line-strong)]">|</span>
                @endif

                @foreach ($movementEntries as $movementEntry)
                    @php
                        $movementKind = (string) ($movementEntry['kind'] ?? 'other');
                        $movementLabel = (string) ($movementEntry['label'] ?? 'Movement');
                        $movementClock = $movementEntry['remaining_label'] ?? null;
                        $movementRemainingSeconds = isset($movementEntry['remaining_seconds'])
                            ? (int) $movementEntry['remaining_seconds']
                            : null;
                        $movementClasses = match ($movementKind) {
                            'incoming_attack' => 'border-red-500/25 bg-red-500/10 text-red-800',
                            'incoming_reinforcement' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-800',
                            'outgoing' => 'border-sky-500/25 bg-sky-500/10 text-sky-800',
                            default => 'border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] text-[var(--color-ink)]',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 {{ $movementClasses }}">
                        <span class="font-semibold">{{ $movementLabel }}</span>
                        @if ($movementClock !== null || $movementRemainingSeconds !== null)
                            <span class="font-mono text-[11px]"
                                @if ($movementRemainingSeconds !== null) x-data="{
                                        remaining: Math.max(0, {{ max(0, $movementRemainingSeconds - max(0, now()->getTimestamp() - $serverReportedAtTimestamp)) }}),
                                        init() {
                                            setInterval(() => {
                                                if (this.remaining > 0) {
                                                    this.remaining--;
                                                }
                                            }, 1000);
                                        },
                                        formatted() {
                                            const hours = String(Math.floor(this.remaining / 3600)).padStart(1, '0');
                                            const minutes = String(Math.floor((this.remaining % 3600) / 60)).padStart(2, '0');
                                            const seconds = String(this.remaining % 60).padStart(2, '0');

                                            return `${hours}:${minutes}:${seconds}`;
                                        }
                                    }"
                                x-text="formatted()" @endif>
                                @if ($movementRemainingSeconds === null)
                                    {{ $movementClock }}
                                @endif
                            </span>
                        @endif
                    </span>
                @endforeach

                @if ($incomingAttackCount > 0 && $movementEntries->where('kind', 'incoming_attack')->isEmpty())
                    <span class="rounded-full border border-red-500/25 bg-red-500/10 px-2.5 py-1 font-semibold text-red-800">
                        Attacks {{ $incomingAttackCount }}
                    </span>
                @endif

                @if ($incomingReinforcementCount > 0 && $movementEntries->where('kind', 'incoming_reinforcement')->isEmpty())
                    <span
                        class="rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 font-semibold text-emerald-800">
                        Support {{ $incomingReinforcementCount }}
                    </span>
                @endif

                @if ($outgoingMovementCount > 0 && $movementEntries->where('kind', 'outgoing')->isEmpty())
                    <span class="rounded-full border border-sky-500/25 bg-sky-500/10 px-2.5 py-1 font-semibold text-sky-800">
                        Outgoing {{ $outgoingMovementCount }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button" wire:click="openVillageSettingsModal({{ $village->id }})"
                class="rounded-full border border-[var(--color-line-strong)] px-2.5 py-1.5 text-[11px] font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Settings
            </button>
            <button type="button" wire:click="toggleVillage({{ $village->id }})"
                class="rounded-full border border-[var(--color-line-strong)] px-2.5 py-1.5 text-[11px] font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                {{ $village->is_active ? 'Pause' : 'Activate' }}
            </button>
            <button type="button" wire:click="requestVillageSync({{ $village->id }})"
                class="rounded-full border border-[var(--color-line-strong)] px-2.5 py-1.5 text-[11px] font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Update
            </button>
        </div>
    </div>
</div>
