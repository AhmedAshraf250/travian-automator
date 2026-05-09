@php
    $resourceState = $village->resourceState;
    $runtimeState = $village->runtimeState;
    $coordinateText = $village->x !== null && $village->y !== null ? "[{$village->x}|{$village->y}]" : '[--|--]';
    $populationText = $village->population > 0 ? (string) $village->population : '--';
    $troopSlots = $runtimeState?->normalizedTroopSlots() ?? array_fill(0, 10, 0);
    $troopSlots = array_pad($troopSlots, 10, 0);
    $troopVector = '[' . implode(', ', $troopSlots) . ']';
    $incomingAttackCount = (int) ($runtimeState?->incoming_attack_count ?? 0);
    $incomingReinforcementCount = (int) ($runtimeState?->incoming_reinforcement_count ?? 0);
    $outgoingMovementCount = (int) ($runtimeState?->outgoing_movement_count ?? 0);
    $movementEntries = collect($runtimeState?->movement_entries ?? []);
    $constructionEntries = collect($runtimeState?->construction_entries ?? []);
    $statusBadgeClasses = $village->is_active
        ? 'border-emerald-500/30 bg-emerald-500/15 text-emerald-800'
        : 'border-amber-500/30 bg-amber-500/15 text-amber-800';
    $statusDotClasses = $village->is_active ? 'bg-emerald-500' : 'bg-amber-500';

    $resourceCards = [
        [
            'short' => '<wood>',
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'value' => $resourceState?->wood ?? 0,
            'production' => $resourceState?->wood_production ?? 0,
        ],
        [
            'short' => '<clay>',
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'value' => $resourceState?->clay ?? 0,
            'production' => $resourceState?->clay_production ?? 0,
        ],
        [
            'short' => '<iron>',
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'value' => $resourceState?->iron ?? 0,
            'production' => $resourceState?->iron_production ?? 0,
        ],
        [
            'short' => '<crop>',
            'capacity' => $resourceState?->granary_capacity ?? 0,
            'value' => $resourceState?->crop ?? 0,
            'production' => $resourceState?->crop_production ?? 0,
        ],
    ];
@endphp

<div wire:key="village-row-{{ $village->id }}"
    class="rounded-[1.1rem] border border-[var(--color-line)] bg-[var(--color-panel)] px-4 py-3">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1 space-y-2.5">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
                <span class="inline-flex items-center justify-center"
                    title="{{ $village->is_active ? 'Enabled' : 'Paused' }}">
                    <span
                        class="h-4.5 w-4.5 rounded-full {{ $statusDotClasses }} shadow-[0_0_0_5px_rgba(34,197,94,0.18)]"></span>
                </span>
                <span class="font-semibold text-[var(--color-ink)]">{{ $village->name }}</span>
                <span class="text-[var(--color-muted)]">{{ $coordinateText }}</span>
                <span
                    class="rounded-full bg-[var(--color-panel-alt)] px-2.5 py-1 text-[11px] font-semibold text-[var(--color-muted)]">[{{ $populationText }}]</span>
                <span
                    class="rounded-full border px-2.5 py-1 font-mono text-[11px] font-semibold {{ $statusBadgeClasses }}">
                    {{ $village->is_active ? '|=(active)=|' : '|=(paused)=|' }}
                </span>
                <span class="font-mono text-[11px] text-[var(--color-muted)]">{{ $troopVector }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2 font-mono text-xs text-[var(--color-ink)]">
                @foreach ($resourceCards as $resourceCard)
                    <span class="rounded-xl bg-[var(--color-panel-alt)] px-3 py-1.5">
                        [{{ $resourceCard['short'] }} {{ $resourceCard['capacity'] }}\{{ $resourceCard['value'] }}
                        +{{ $resourceCard['production'] }}/h]
                    </span>
                @endforeach

            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
                @foreach ($movementEntries as $movementEntry)
                    @php
                        $movementKind = (string) ($movementEntry['kind'] ?? 'other');
                        $movementLabel = (string) ($movementEntry['label'] ?? 'Movement');
                        $movementClock = $movementEntry['remaining_label'] ?? null;
                        $normalizedMovementLabel = mb_strtolower($movementLabel);
                        $movementClasses = match ($movementKind) {
                            'incoming_attack' => 'border-red-500/35 bg-red-500/8',
                            'incoming_reinforcement' => 'border-emerald-500/35 bg-emerald-500/8',
                            'outgoing' => str_contains($normalizedMovementLabel, 'attack') || str_contains($normalizedMovementLabel, 'هجوم')
                                ? 'border-amber-500/35 bg-amber-500/8'
                                : 'border-sky-500/35 bg-sky-500/8',
                            default => 'border-stone-400/30 bg-stone-500/8',
                        };
                        $movementTextClasses = match ($movementKind) {
                            'incoming_attack' => 'text-red-700',
                            'incoming_reinforcement' => 'text-emerald-700',
                            'outgoing' => str_contains($normalizedMovementLabel, 'attack') || str_contains($normalizedMovementLabel, 'هجوم')
                                ? 'text-amber-700'
                                : 'text-sky-700',
                            default => 'text-stone-700',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 {{ $movementClasses }}">
                        <span class="font-semibold leading-none {{ $movementTextClasses }}">
                            {{ $movementLabel }}
                        </span>
                        @if ($movementClock !== null)
                            <span class="font-mono text-[11px] leading-none text-[var(--color-muted)]">
                                {{ $movementClock }}
                            </span>
                        @endif
                    </span>
                @endforeach

                @if ($incomingAttackCount > 0 && $movementEntries->where('kind', 'incoming_attack')->isEmpty())
                    <span class="inline-flex items-center gap-2 rounded-full border border-red-500/35 bg-red-500/8 px-3 py-1.5">
                        <span class="font-semibold leading-none text-red-700">Incoming attacks {{ $incomingAttackCount }}</span>
                    </span>
                @endif
                @if ($incomingReinforcementCount > 0 && $movementEntries->where('kind', 'incoming_reinforcement')->isEmpty())
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/35 bg-emerald-500/8 px-3 py-1.5">
                        <span class="font-semibold leading-none text-emerald-700">Incoming support {{ $incomingReinforcementCount }}</span>
                    </span>
                @endif
                @if ($outgoingMovementCount > 0 && $movementEntries->where('kind', 'outgoing')->isEmpty())
                    <span class="inline-flex items-center gap-2 rounded-full border border-amber-500/35 bg-amber-500/8 px-3 py-1.5">
                        <span class="font-semibold leading-none text-amber-700">Outgoing movements {{ $outgoingMovementCount }}</span>
                    </span>
                @endif

                @if ($movementEntries->isNotEmpty() && $constructionEntries->isNotEmpty())
                    <span class="h-5 w-px bg-[var(--color-line-strong)]"></span>
                @endif

                @foreach ($constructionEntries as $constructionEntry)
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-1.5">
                        <span class="font-semibold leading-none text-[var(--color-ink)]">
                            {{ $constructionEntry['building_name'] ?? 'Building' }}
                        </span>
                        @if (array_key_exists('target_level', $constructionEntry) && $constructionEntry['target_level'] !== null)
                            <span class="font-semibold leading-none text-[var(--color-accent)]">
                                Level {{ $constructionEntry['target_level'] }}
                            </span>
                        @endif
                        @if (! empty($constructionEntry['remaining_label']))
                            <span class="font-mono text-[11px] leading-none text-[var(--color-muted)]">
                                {{ $constructionEntry['remaining_label'] }} hrs.
                            </span>
                        @endif
                        @if (! empty($constructionEntry['finish_label']))
                            <span class="font-mono text-[11px] leading-none text-[var(--color-muted)]">
                                done at {{ $constructionEntry['finish_label'] }}
                            </span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2 lg:justify-end">
            <button type="button" wire:click="toggleVillage({{ $village->id }})"
                class="rounded-full border border-[var(--color-line-strong)] px-3 py-2 text-xs font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                {{ $village->is_active ? 'Pause' : 'Activate' }}
            </button>
            <button type="button" wire:click="requestVillageSync({{ $village->id }})"
                class="rounded-full border border-[var(--color-line-strong)] px-3 py-2 text-xs font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                Update
            </button>
            <button type="button"
                class="rounded-full border border-[var(--color-line)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]">
                Settings
            </button>
        </div>
    </div>
</div>
