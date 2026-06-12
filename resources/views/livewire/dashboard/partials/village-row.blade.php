@php
    $resourceState = $village->resourceState;
    $runtimeState = $village->runtimeState;
    $settings = $village->settings;
    $coordinateText = $village->x !== null && $village->y !== null ? "[{$village->x}|{$village->y}]" : '[--|--]';
    $populationText = $village->population > 0 ? (string) $village->population : '--';
    $troopSlots = $runtimeState?->normalizedTroopSlots() ?? array_fill(0, 11, 0);
    $troopSlots = array_slice(array_pad($troopSlots, 11, 0), 0, 11);
    $troopVector = '[' . implode(',', $troopSlots) . ']';
    $tribeId = (int) ($runtimeState?->tribe_id ?? 0);
    $troopIconOffset = match ($tribeId) {
        1 => 0,
        2 => 10,
        3 => 20,
        default => 0,
    };
    $troopUnitNames = match ($tribeId) {
        1 => ['Legionnaire', 'Praetorian', 'Imperian', 'Equites Legati', 'Equites Imperatoris', 'Equites Caesaris', 'Battering Ram', 'Fire Catapult', 'Senator', 'Settler'],
        2 => ['Clubswinger', 'Spearman', 'Axeman', 'Scout', 'Paladin', 'Teutonic Knight', 'Ram', 'Catapult', 'Chief', 'Settler'],
        3 => ['Phalanx', 'Swordsman', 'Pathfinder', 'Theutates Thunder', 'Druidrider', 'Haeduan', 'Ram', 'Trebuchet', 'Chieftain', 'Settler'],
        default => ['Unit 1', 'Unit 2', 'Unit 3', 'Unit 4', 'Unit 5', 'Unit 6', 'Unit 7', 'Unit 8', 'Unit 9', 'Unit 10'],
    };
    $troopIconEntries = collect([
        [
            'name' => 'Hero',
            'count' => (int) ($troopSlots[0] ?? 0),
            'icon' => 'assets/troops-icons/hero.png',
        ],
    ])->merge(
        collect(array_slice($troopSlots, 1, 10))->map(static fn ($count, $index): array => [
            'name' => $troopUnitNames[$index] ?? ('Unit ' . ($index + 1)),
            'count' => (int) $count,
            'icon' => 'assets/troops-icons/u' . ($troopIconOffset + $index + 1) . '.png',
        ])
    );
    $incomingAttackCount = (int) ($runtimeState?->incoming_attack_count ?? 0);
    $incomingReinforcementCount = (int) ($runtimeState?->incoming_reinforcement_count ?? 0);
    $outgoingMovementCount = (int) ($runtimeState?->outgoing_movement_count ?? 0);
    $serverReportedAtTimestamp = $runtimeState?->server_reported_at?->getTimestamp() ?? now()->getTimestamp();
    $elapsedSeconds = max(0, now()->getTimestamp() - $serverReportedAtTimestamp);
    $entryIsStillRunning = static function (mixed $entry) use ($elapsedSeconds): bool {
        if (! is_array($entry)) {
            return false;
        }

        if (! isset($entry['remaining_seconds'])) {
            return true;
        }

        return (int) $entry['remaining_seconds'] > $elapsedSeconds;
    };
    $rawMovementEntries = collect($runtimeState?->movement_entries ?? []);
    $rawConstructionEntries = collect($runtimeState?->construction_entries ?? []);
    $movementEntries = $rawMovementEntries->filter($entryIsStillRunning)->values();
    $constructionEntries = $rawConstructionEntries->filter($entryIsStillRunning)->take(2);
    $remainingConstructionCount = max(0, $rawConstructionEntries->filter($entryIsStillRunning)->count() - $constructionEntries->count());
    $hasConstructionSummary = $constructionEntries->isNotEmpty() || $remainingConstructionCount > 0;
    $hasExpiredIncomingAttack = $rawMovementEntries->contains(static fn (mixed $entry): bool => is_array($entry)
        && ($entry['kind'] ?? null) === 'incoming_attack'
        && isset($entry['remaining_seconds'])
        && (int) $entry['remaining_seconds'] <= $elapsedSeconds);
    $hasExpiredIncomingReinforcement = $rawMovementEntries->contains(static fn (mixed $entry): bool => is_array($entry)
        && ($entry['kind'] ?? null) === 'incoming_reinforcement'
        && isset($entry['remaining_seconds'])
        && (int) $entry['remaining_seconds'] <= $elapsedSeconds);
    $hasExpiredOutgoing = $rawMovementEntries->contains(static fn (mixed $entry): bool => is_array($entry)
        && ($entry['kind'] ?? null) === 'outgoing'
        && isset($entry['remaining_seconds'])
        && (int) $entry['remaining_seconds'] <= $elapsedSeconds);
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
    $movementVisual = static function (string $kind, string $label): array {
        $normalizedLabel = mb_strtolower($label);

        if (str_contains($normalizedLabel, 'adventure') || str_contains($normalizedLabel, 'مغام')) {
            return [
                'icon' => 'assets/movements-icons/adventure.png',
                'style' => 'background-color: #5254e6; border-color: rgba(82, 84, 230, 0.45); color: #ffffff;',
                'alt' => 'Adventure',
            ];
        }

        return match ($kind) {
            'incoming_attack' => [
                'icon' => 'assets/movements-icons/att1.gif',
                'style' => 'background-color: #fee2e2; border-color: rgba(239, 68, 68, 0.35); color: #991b1b;',
                'alt' => 'Incoming attack',
            ],
            'outgoing' => [
                'icon' => 'assets/movements-icons/att2.gif',
                'style' => 'background-color: #fff6b5; border-color: rgba(217, 119, 6, 0.35); color: #624100;',
                'alt' => 'Outgoing movement',
            ],
            'incoming_reinforcement' => [
                'icon' => str_contains($normalizedLabel, 'oasis') || str_contains($normalizedLabel, 'واحة')
                    ? 'assets/movements-icons/def3.gif'
                    : 'assets/movements-icons/def2.gif',
                'style' => 'background-color: #daeeb9; border-color: rgba(101, 163, 13, 0.35); color: #24521d;',
                'alt' => 'Incoming reinforcement',
            ],
            default => [
                'icon' => 'assets/movements-icons/def1.gif',
                'style' => 'background-color: #daeeb9; border-color: rgba(101, 163, 13, 0.35); color: #24521d;',
                'alt' => 'Movement',
            ],
        };
    };
    $statusDotClasses = $village->is_active ? 'bg-emerald-500' : 'bg-amber-500';
    $fieldsAutomationEnabled = ! ($settings?->pause_fields ?? false);
    $buildingsAutomationEnabled = ! ($settings?->pause_buildings ?? false);
    $troopTrainingEnabled = (bool) ($settings?->troop_training_enabled ?? false);
    $celebrationEnabled = (bool) ($settings?->celebration_enabled ?? false);
    $enabledSignalClasses = 'bg-emerald-500/10 text-emerald-800';
    $disabledSignalClasses = 'bg-slate-200 text-slate-600';
    $resourceCards = [
        [
            'short' => 'W',
            'value' => $resourceState?->wood ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'production' => $resourceState?->wood_production ?? 0,
            'background' => '#c5761e',
            'color' => '#fff8ef',
            'icon' => 'assets/res-icons/lumber_small.png',
        ],
        [
            'short' => 'C',
            'value' => $resourceState?->clay ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'production' => $resourceState?->clay_production ?? 0,
            'background' => '#b65040',
            'color' => '#fff7f5',
            'icon' => 'assets/res-icons/clay_small.png',
        ],
        [
            'short' => 'I',
            'value' => $resourceState?->iron ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'production' => $resourceState?->iron_production ?? 0,
            'background' => '#78869c',
            'color' => '#f8fbff',
            'icon' => 'assets/res-icons/iron_small.png',
        ],
        [
            'short' => 'G',
            'value' => $resourceState?->crop ?? 0,
            'capacity' => $resourceState?->granary_capacity ?? 0,
            'production' => $resourceState?->crop_production ?? 0,
            'background' => '#ffd11d',
            'color' => '#342700',
            'icon' => 'assets/res-icons/crop_small.png',
        ],
    ];
    $hasMovementSummary = $movementEntries->isNotEmpty()
        || ($incomingAttackCount > 0 && $movementEntries->where('kind', 'incoming_attack')->isEmpty() && ! $hasExpiredIncomingAttack)
        || ($incomingReinforcementCount > 0 && $movementEntries->where('kind', 'incoming_reinforcement')->isEmpty() && ! $hasExpiredIncomingReinforcement)
        || ($outgoingMovementCount > 0 && $movementEntries->where('kind', 'outgoing')->isEmpty() && ! $hasExpiredOutgoing);
@endphp

<div wire:key="village-row-{{ $village->id }}" class="relative">
    <span class="absolute -left-4 top-5 h-px w-4 bg-[var(--color-line-strong)]"></span>

    <div class="grid gap-2 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] px-3 py-2 shadow-sm xl:grid-cols-[minmax(10.5rem,0.75fr)_minmax(0,3.35fr)_auto] xl:items-center">
        <div class="min-w-0">
            <div class="flex min-w-0 items-center gap-2" title="{{ $village->name }} {{ $coordinateText }} · Pop {{ $populationText }}">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $statusDotClasses }}"
                    title="{{ $village->is_active ? 'Enabled' : 'Paused' }}"></span>
                <span class="min-w-0 truncate text-sm font-semibold text-[var(--color-ink)]">{{ $village->name }}</span>
                <span class="shrink-0 text-xs font-semibold text-[var(--color-muted)]">{{ $coordinateText }}</span>
                <span class="shrink-0 text-xs font-semibold text-[var(--color-muted)]">Pop {{ $populationText }}</span>
            </div>

            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-[var(--color-muted)]">
                <span class="rounded-md px-2 py-0.5 {{ $fieldsAutomationEnabled ? $enabledSignalClasses : $disabledSignalClasses }}">
                    F {{ $fieldsAutomationEnabled ? 'ON' : 'OFF' }}
                </span>
                <span class="rounded-md px-2 py-0.5 {{ $buildingsAutomationEnabled ? $enabledSignalClasses : $disabledSignalClasses }}">
                    B {{ $buildingsAutomationEnabled ? 'ON' : 'OFF' }}
                </span>
                <span class="rounded-md px-2 py-0.5 {{ $troopTrainingEnabled ? $enabledSignalClasses : $disabledSignalClasses }}">
                    T {{ $troopTrainingEnabled ? 'ON' : 'OFF' }}
                </span>
                <span class="rounded-md px-2 py-0.5 {{ $celebrationEnabled ? $enabledSignalClasses : $disabledSignalClasses }}">
                    C {{ $celebrationEnabled ? 'ON' : 'OFF' }}
                </span>
            </div>
        </div>

        <div class="min-w-0 space-y-1.5 text-[11px]">
            <div class="grid min-w-0 grid-cols-1 items-center gap-1.5 sm:grid-cols-[repeat(2,minmax(8rem,1fr))] lg:grid-cols-[repeat(4,minmax(7.6rem,1fr))_minmax(14rem,1.75fr)]">
                @foreach ($resourceCards as $resourceCard)
                    <span class="inline-flex h-7 w-full items-center gap-1.5 rounded-md border border-[var(--color-line)] bg-white px-1.5 font-mono text-[10px] leading-none text-[var(--color-ink)] shadow-sm">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded"
                            style="background-color: {{ $resourceCard['background'] }}; color: {{ $resourceCard['color'] }}">
                            <img src="{{ asset($resourceCard['icon']) }}" alt="{{ $resourceCard['short'] }}"
                                class="h-4 w-4 shrink-0 object-contain"
                                onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" />
                            <span class="hidden text-[9px] font-extrabold">{{ $resourceCard['short'] }}</span>
                        </span>
                        <span class="inline-flex min-w-0 items-baseline gap-1 truncate">
                            <span class="shrink-0 font-semibold text-slate-400">{{ $resourceCard['capacity'] }} /</span>
                            <span class="shrink-0 text-[11px] font-extrabold text-[var(--color-ink)]">{{ $resourceCard['value'] }}</span>
                            <span class="shrink-0 font-extrabold" style="color: #11883d">+{{ $resourceCard['production'] }}/h</span>
                        </span>
                    </span>
                @endforeach

                <span class="inline-flex h-7 w-full min-w-0 items-center gap-1 overflow-hidden rounded-md bg-[var(--color-panel-alt)] px-1.5 font-mono text-[10px] font-semibold text-[var(--color-muted)]"
                    title="Troops {{ $troopVector }}">
                    @foreach ($troopIconEntries as $troopIconEntry)
                        <span class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-white/65 ring-1 ring-black/5 {{ $troopIconEntry['count'] > 0 ? '' : 'opacity-45' }}"
                            title="{{ $troopIconEntry['name'] }}: {{ $troopIconEntry['count'] }}">
                            <img src="{{ asset($troopIconEntry['icon']) }}" alt="{{ $troopIconEntry['name'] }}"
                                class="h-4 w-4 object-contain"
                                onerror="this.classList.add('hidden')" />
                            <span class="absolute -bottom-1 -right-1 max-w-6 truncate rounded-sm bg-white px-[2px] text-[8px] font-extrabold leading-3 text-[var(--color-ink)] shadow-sm">
                                {{ $troopIconEntry['count'] }}
                            </span>
                        </span>
                    @endforeach
                </span>
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <div class="flex min-w-0 flex-wrap items-center gap-1.5">
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
                        $constructionEndsAtTimestamp = $constructionRemainingSeconds !== null
                            ? ($serverReportedAtTimestamp + $constructionRemainingSeconds) * 1000
                            : null;
                    @endphp
                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-md border px-2 py-1 font-semibold shadow-sm"
                        style="background-color: #f88c1f; border-color: rgba(248, 140, 31, 0.55); color: #2f1600;"
                        @if ($constructionRemainingSeconds !== null) x-data="{
                                endsAt: {{ $constructionEndsAtTimestamp }},
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
                            x-show="remaining > 0" x-cloak @endif>
                        <span class="truncate font-semibold">{{ $constructionEntry['building_name'] ?? 'Building' }}</span>
                        @if (array_key_exists('target_level', $constructionEntry) && $constructionEntry['target_level'] !== null)
                            <span class="font-semibold">Lv {{ $constructionEntry['target_level'] }}</span>
                        @endif
                        @if (!empty($constructionEntry['remaining_label']) || $constructionRemainingSeconds !== null)
                            <span class="font-mono text-[11px]"
                                @if ($constructionRemainingSeconds !== null) x-text="formatted()" @endif>
                                @if ($constructionRemainingSeconds !== null)
                                    {{ $constructionInitialClock }}
                                @else
                                    {{ $constructionEntry['remaining_label'] }}
                                @endif
                            </span>
                        @endif
                        @if (!empty($constructionEntry['finish_label']))
                            <span class="font-mono text-[11px]">Ends {{ $constructionEntry['finish_label'] }}</span>
                        @endif
                    </span>
                    @empty
                    <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1 font-semibold text-[var(--color-muted)]">
                            No construction
                        </span>
                    @endforelse

                    @if ($remainingConstructionCount > 0)
                        <span class="rounded-md bg-[var(--color-panel-alt)] px-2 py-1 font-semibold text-[var(--color-muted)]">
                            +{{ $remainingConstructionCount }}
                        </span>
                    @endif
                </div>

                @if ($hasConstructionSummary && $hasMovementSummary)
                    <span class="hidden h-6 w-[2px] shrink-0 rounded-full bg-[var(--color-line-strong)] ring-2 ring-[var(--color-panel)] sm:inline-block"></span>
                @endif

                <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                    @foreach ($movementEntries as $movementEntry)
                    @php
                        $movementKind = (string) ($movementEntry['kind'] ?? 'other');
                        $movementLabel = (string) ($movementEntry['label'] ?? 'Movement');
                        $movementClock = $movementEntry['remaining_label'] ?? null;
                        $movementRemainingSeconds = isset($movementEntry['remaining_seconds'])
                            ? (int) $movementEntry['remaining_seconds']
                            : null;
                        $movementEndsAtTimestamp = $movementRemainingSeconds !== null
                            ? ($serverReportedAtTimestamp + $movementRemainingSeconds) * 1000
                            : null;
                        $movementDisplay = $movementVisual($movementKind, $movementLabel);
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 font-semibold shadow-sm"
                        style="{{ $movementDisplay['style'] }}"
                        @if ($movementRemainingSeconds !== null) x-data="{
                                endsAt: {{ $movementEndsAtTimestamp }},
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
                            x-show="remaining > 0" x-cloak @endif>
                        <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded bg-white/55">
                            <img src="{{ asset($movementDisplay['icon']) }}" alt="{{ $movementDisplay['alt'] }}"
                                class="max-h-3.5 max-w-3.5 object-contain"
                                onerror="this.parentElement.classList.add('hidden')" />
                        </span>
                        <span class="font-semibold">{{ $movementLabel }}</span>
                        @if ($movementClock !== null || $movementRemainingSeconds !== null)
                            <span class="font-mono text-[11px]"
                                @if ($movementRemainingSeconds !== null) x-text="formatted()" @endif>
                                @if ($movementRemainingSeconds === null)
                                    {{ $movementClock }}
                                @endif
                            </span>
                        @endif
                    </span>
                    @endforeach

                    @if ($incomingAttackCount > 0 && $movementEntries->where('kind', 'incoming_attack')->isEmpty() && ! $hasExpiredIncomingAttack)
                        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 font-semibold shadow-sm"
                            style="background-color: #fee2e2; border-color: rgba(239, 68, 68, 0.35); color: #991b1b;">
                            <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded bg-white/55">
                                <img src="{{ asset('assets/movements-icons/att1.gif') }}" alt="Incoming attack" class="max-h-3.5 max-w-3.5 object-contain" />
                            </span>
                            Attacks {{ $incomingAttackCount }}
                        </span>
                    @endif

                    @if ($incomingReinforcementCount > 0 && $movementEntries->where('kind', 'incoming_reinforcement')->isEmpty() && ! $hasExpiredIncomingReinforcement)
                        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 font-semibold shadow-sm"
                            style="background-color: #daeeb9; border-color: rgba(101, 163, 13, 0.35); color: #24521d;">
                            <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded bg-white/55">
                                <img src="{{ asset('assets/movements-icons/def2.gif') }}" alt="Incoming reinforcement" class="max-h-3.5 max-w-3.5 object-contain" />
                            </span>
                            Support {{ $incomingReinforcementCount }}
                        </span>
                    @endif

                    @if ($outgoingMovementCount > 0 && $movementEntries->where('kind', 'outgoing')->isEmpty() && ! $hasExpiredOutgoing)
                        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 font-semibold shadow-sm"
                            style="background-color: #fff6b5; border-color: rgba(217, 119, 6, 0.35); color: #624100;">
                            <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded bg-white/55">
                                <img src="{{ asset('assets/movements-icons/att2.gif') }}" alt="Outgoing movement" class="max-h-3.5 max-w-3.5 object-contain" />
                            </span>
                            Outgoing {{ $outgoingMovementCount }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-1.5 xl:justify-end">
            <button type="button" wire:click="openVillageSettingsModal({{ $village->id }})"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]"
                title="Village settings">
                &#9881;
            </button>
            <button type="button" wire:click="toggleVillage({{ $village->id }})"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]"
                title="{{ $village->is_active ? 'Pause village' : 'Activate village' }}"
                aria-label="{{ $village->is_active ? 'Pause village' : 'Activate village' }}">
                {!! $village->is_active ? '&#9208;' : '&#9654;' !!}
            </button>
            <button type="button" wire:click="requestVillageSync({{ $village->id }})"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]"
                title="Sync and run village automation"
                aria-label="Sync and run village automation">
                &#8635;
            </button>
        </div>
    </div>
</div>
