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
    $entryRecordedAtTimestamp = static function (mixed $entry) use ($serverReportedAtTimestamp): int {
        if (! is_array($entry)) {
            return $serverReportedAtTimestamp;
        }

        $recordedAt = $entry['recorded_at'] ?? null;

        if (! is_string($recordedAt) || $recordedAt === '') {
            return $serverReportedAtTimestamp;
        }

        try {
            return \Carbon\CarbonImmutable::parse($recordedAt)->getTimestamp();
        } catch (\Throwable) {
            return $serverReportedAtTimestamp;
        }
    };
    $entryElapsedSeconds = static fn (mixed $entry): int => max(0, now()->getTimestamp() - $entryRecordedAtTimestamp($entry));
    $entryIsStillRunning = static function (mixed $entry) use ($entryElapsedSeconds): bool {
        if (! is_array($entry)) {
            return false;
        }

        if (! isset($entry['remaining_seconds'])) {
            return true;
        }

        return (int) $entry['remaining_seconds'] > $entryElapsedSeconds($entry);
    };
    $rawMovementEntries = collect($runtimeState?->movement_entries ?? []);
    $rawConstructionEntries = collect($runtimeState?->construction_entries ?? []);
    $movementEntries = $rawMovementEntries->filter($entryIsStillRunning)->values();
    $runningConstructionEntries = $rawConstructionEntries->filter($entryIsStillRunning)->values();
    $activeConstructionMatches = $runningConstructionEntries
        ->filter(static fn (mixed $entry): bool => is_array($entry))
        ->values();
    $constructionMatchesCandidate = static function (string $name, int $targetLevel) use ($activeConstructionMatches): bool {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            return false;
        }

        return $activeConstructionMatches->contains(static fn (mixed $entry): bool => is_array($entry)
            && trim((string) ($entry['building_name'] ?? '')) === $normalizedName
            && (int) ($entry['target_level'] ?? 0) === $targetLevel);
    };
    $constructionEntries = ($runningConstructionEntries->isNotEmpty() ? $runningConstructionEntries : $rawConstructionEntries)->take(2);
    $remainingConstructionCount = max(0, ($runningConstructionEntries->isNotEmpty() ? $runningConstructionEntries : $rawConstructionEntries)->count() - $constructionEntries->count());
    $hasConstructionSummary = $constructionEntries->isNotEmpty() || $remainingConstructionCount > 0;
    $hasExpiredIncomingAttack = $rawMovementEntries->contains(static fn (mixed $entry): bool => is_array($entry)
        && ($entry['kind'] ?? null) === 'incoming_attack'
        && isset($entry['remaining_seconds'])
        && (int) $entry['remaining_seconds'] <= $entryElapsedSeconds($entry));
    $hasExpiredIncomingReinforcement = $rawMovementEntries->contains(static fn (mixed $entry): bool => is_array($entry)
        && ($entry['kind'] ?? null) === 'incoming_reinforcement'
        && isset($entry['remaining_seconds'])
        && (int) $entry['remaining_seconds'] <= $entryElapsedSeconds($entry));
    $hasExpiredOutgoing = $rawMovementEntries->contains(static fn (mixed $entry): bool => is_array($entry)
        && ($entry['kind'] ?? null) === 'outgoing'
        && isset($entry['remaining_seconds'])
        && (int) $entry['remaining_seconds'] <= $entryElapsedSeconds($entry));
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
    $heroResourcesEnabled = (bool) ($settings?->hero_resources_enabled ?? true);
    $troopTrainingEnabled = (bool) ($settings?->troop_training_enabled ?? false);
    $celebrationEnabled = (bool) ($settings?->celebration_enabled ?? false);
    $isVillagePaused = ! $village->is_active;
    $villageNameClasses = $isVillagePaused ? 'text-amber-700' : 'text-[var(--color-ink)]';
    $enabledSignalClasses = 'border-emerald-700 bg-emerald-500 text-white shadow-inner ring-1 ring-emerald-700/30';
    $disabledSignalClasses = 'border-slate-300 bg-slate-200 text-slate-500 shadow-sm hover:bg-slate-300';
    $pausedSignalClasses = 'border-amber-500 bg-amber-300 text-amber-950 shadow-inner';
    $controlButtonClasses = static fn (bool $isEnabled): string => $isVillagePaused
        ? $pausedSignalClasses
        : ($isEnabled ? $enabledSignalClasses : $disabledSignalClasses);
    $buildingIconForGid = static function (int $gid): ?string {
        if ($gid < 1) {
            return null;
        }

        foreach ([
            "assets/buildings-icons/type{$gid}_small.png",
            "assets/buildings-icons/type{$gid}_teahouse_small.png",
        ] as $candidate) {
            if (file_exists(public_path($candidate))) {
                return $candidate;
            }
        }

        return null;
    };
    $fieldLabelForGid = static fn (int $gid): string => match ($gid) {
        1 => 'Woodcutter',
        2 => 'Clay Pit',
        3 => 'Iron Mine',
        4 => 'Cropland',
        default => 'Field',
    };
    $fieldControls = $village->buildings
        ->filter(static fn ($building): bool => (int) $building->slot_id >= 1
            && (int) $building->slot_id <= 18
            && (int) $building->building_gid >= 1
            && (int) $building->building_gid <= 4)
        ->sortBy('slot_id')
        ->values()
        ->map(fn ($building): array => [
            'slot_id' => (int) $building->slot_id,
            'gid' => (int) $building->building_gid,
            'field_key' => \App\Application\Travian\TravianBuildingCatalog::fieldKeyForGid((int) $building->building_gid),
            'name' => $building->building_type ?: $fieldLabelForGid((int) $building->building_gid),
            'level' => (int) $building->current_level,
            'enabled' => (bool) $building->automation_enabled,
            'icon' => $buildingIconForGid((int) $building->building_gid),
        ]);
    $buildingTargetsBySlot = $village->buildingTargets->keyBy('slot_id');
    $buildingControls = $village->buildings
        ->filter(static fn ($building): bool => (int) $building->slot_id >= 19
            && (int) $building->slot_id <= 40
            && (int) $building->building_gid > 0)
        ->sortBy('slot_id')
        ->values()
        ->map(fn ($building): array => [
            'slot_id' => (int) $building->slot_id,
            'gid' => (int) $building->building_gid,
            'name' => $building->building_type ?: (\App\Application\Travian\TravianBuildingCatalog::nameForGid((int) $building->building_gid) ?? 'Building'),
            'level' => (int) $building->current_level,
            'enabled' => (bool) $building->automation_enabled,
            'icon' => $buildingIconForGid((int) $building->building_gid),
            'target_enabled' => (bool) ($buildingTargetsBySlot->get((int) $building->slot_id)?->is_enabled ?? true),
        ]);
    $constructionSchedule = is_array($settings?->construction_schedule) ? $settings->construction_schedule : [];
    $pinnedScheduleKeys = collect($constructionSchedule['pinned'] ?? [])->filter(fn ($key): bool => is_string($key) && $key !== '')->values()->all();
    $heldScheduleKeys = collect($constructionSchedule['held'] ?? [])->filter(fn ($key): bool => is_string($key) && $key !== '')->values()->all();
    $effectiveFieldPriority = (bool) ($settings?->inherit_from_account ?? true)
        ? ($globalFieldPriority ?? \App\Models\VillageSetting::defaultFieldPriority())
        : (is_array($settings?->field_priority) ? $settings->field_priority : \App\Models\VillageSetting::defaultFieldPriority());
    $fieldPassesPriorityLeadGuard = static function (array $candidateField, $fieldControls, array $priorityMap): bool {
        $candidateFieldKey = $candidateField['field_key'] ?? null;

        if (! is_string($candidateFieldKey) || $candidateFieldKey === '') {
            return false;
        }

        $candidatePriority = (int) ($priorityMap[$candidateFieldKey] ?? 999);
        $candidateNextLevel = (int) ($candidateField['level'] ?? 0) + 1;
        $minLevelByField = [];

        foreach ($fieldControls as $field) {
            if (! is_array($field) || ! (bool) ($field['enabled'] ?? false)) {
                continue;
            }

            $fieldKey = $field['field_key'] ?? null;

            if (! is_string($fieldKey) || $fieldKey === '') {
                continue;
            }

            $fieldLevel = (int) ($field['level'] ?? 0);
            $minLevelByField[$fieldKey] = isset($minLevelByField[$fieldKey])
                ? min($minLevelByField[$fieldKey], $fieldLevel)
                : $fieldLevel;
        }

        foreach ($minLevelByField as $fieldKey => $minLevel) {
            if ($fieldKey === $candidateFieldKey) {
                continue;
            }

            $otherPriority = (int) ($priorityMap[$fieldKey] ?? 999);
            $allowedLead = max(2, abs($otherPriority - $candidatePriority));

            if ($candidateNextLevel > ((int) $minLevel + $allowedLead)) {
                return false;
            }
        }

        return true;
    };
    $fieldScheduleEntries = collect();
    $buildingScheduleEntries = collect();

    if ($fieldsAutomationEnabled) {
        $fieldControls
            ->filter(static fn (array $field): bool => $field['enabled']
                && $field['level'] < 10
                && $fieldPassesPriorityLeadGuard($field, $fieldControls, $effectiveFieldPriority))
            ->sortBy(function (array $field) use ($effectiveFieldPriority): string {
                $fieldKey = $field['field_key'] ?? 'wood';

                return sprintf('%03d-%03d-%03d', (int) ($effectiveFieldPriority[$fieldKey] ?? 999), $field['level'], $field['slot_id']);
            })
            ->each(function (array $field) use (&$fieldScheduleEntries, $constructionMatchesCandidate): void {
                $nextLevel = $field['level'] + 1;

                if ($constructionMatchesCandidate($field['name'], $nextLevel)) {
                    return;
                }

                $fieldScheduleEntries->push([
                    'key' => "field:{$field['slot_id']}:{$nextLevel}",
                    'kind' => 'F',
                    'name' => $field['name'],
                    'slot_id' => $field['slot_id'],
                    'icon' => $field['icon'],
                    'level_label' => "{$field['level']}->{$nextLevel}",
                ]);
            });
    }

    if ($buildingsAutomationEnabled) {
        $village->buildingTargets
            ->sortBy('priority')
            ->each(function ($target) use (&$buildingScheduleEntries, $village, $buildingIconForGid, $constructionMatchesCandidate): void {
                if (! $target->is_enabled || (int) $target->target_level < 1) {
                    return;
                }

                $slot = $village->buildings->firstWhere('slot_id', (int) $target->slot_id);

                if ($slot === null || ! (bool) $slot->automation_enabled) {
                    return;
                }

                $currentLevel = (int) $slot->current_level;
                $nextLevel = (int) $slot->building_gid === 0 ? 1 : $currentLevel + 1;

                if ($nextLevel > (int) $target->target_level) {
                    return;
                }

                $gid = (int) ($target->building_gid ?: $slot->building_gid);
                $buildingName = $target->building_type ?: (\App\Application\Travian\TravianBuildingCatalog::nameForGid($gid) ?? 'Building');

                if ($constructionMatchesCandidate($buildingName, $nextLevel)) {
                    return;
                }

                $buildingScheduleEntries->push([
                    'key' => "building:{$target->slot_id}:{$nextLevel}",
                    'kind' => 'B',
                    'name' => $buildingName,
                    'slot_id' => (int) $target->slot_id,
                    'icon' => $buildingIconForGid($gid),
                    'level_label' => "{$currentLevel}->{$nextLevel}",
                ]);
            });
    }

    $pinnedSchedulePositions = array_flip($pinnedScheduleKeys);
    $orderScheduleEntries = static fn ($entries) => $entries
        ->values()
        ->map(static fn (array $entry, int $index): array => [
                'entry' => [
                    ...$entry,
                    'pinned' => in_array($entry['key'], $pinnedScheduleKeys, true),
                    'held' => in_array($entry['key'], $heldScheduleKeys, true),
                    'reserve' => false,
                ],
                'index' => $index,
                'pinned_position' => $pinnedSchedulePositions[$entry['key']] ?? PHP_INT_MAX,
            ])
            ->sortBy([
                ['pinned_position', 'asc'],
                ['index', 'asc'],
            ])
            ->pluck('entry')
            ->values();
    $orderedScheduleEntries = $orderScheduleEntries($fieldScheduleEntries->concat($buildingScheduleEntries));
    $primaryScheduleEntries = $orderedScheduleEntries->take(8)->values();
    $buildingReserveEntries = collect();

    if ($buildingScheduleEntries->isNotEmpty() && $primaryScheduleEntries->where('kind', 'B')->isEmpty()) {
        $primaryScheduleKeys = $primaryScheduleEntries->pluck('key')->all();
        $buildingReserveEntries = $orderScheduleEntries($buildingScheduleEntries)
            ->reject(static fn (array $entry): bool => in_array($entry['key'], $primaryScheduleKeys, true))
            ->take(2)
            ->map(static fn (array $entry): array => [
                ...$entry,
                'reserve' => true,
            ])
            ->values();
    }

    $scheduleEntries = $buildingReserveEntries->isNotEmpty()
        ? $primaryScheduleEntries->concat($buildingReserveEntries)->take(10)->values()
        : $orderedScheduleEntries->take(10)->values();
    $warehouseCapacity = $resourceState?->warehouse_capacity ?? 0;
    $granaryCapacity = $resourceState?->granary_capacity ?? 0;
    $resourceUsagePercent = static fn (int $value, int $capacity): int => $capacity > 0
        ? min(100, max(0, (int) round(($value / $capacity) * 100)))
        : 0;
    $resourceCards = [
        [
            'short' => 'W',
            'label' => 'Wood',
            'value' => $resourceState?->wood ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'capacity_label' => 'warehouse',
            'production' => $resourceState?->wood_production ?? 0,
            'usage_percent' => $resourceUsagePercent((int) ($resourceState?->wood ?? 0), (int) ($resourceState?->warehouse_capacity ?? 0)),
            'background' => '#c5761e',
            'color' => '#fff8ef',
            'icon' => 'assets/res-icons/lumber_small.png',
        ],
        [
            'short' => 'C',
            'label' => 'Clay',
            'value' => $resourceState?->clay ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'capacity_label' => 'warehouse',
            'production' => $resourceState?->clay_production ?? 0,
            'usage_percent' => $resourceUsagePercent((int) ($resourceState?->clay ?? 0), (int) ($resourceState?->warehouse_capacity ?? 0)),
            'background' => '#b65040',
            'color' => '#fff7f5',
            'icon' => 'assets/res-icons/clay_small.png',
        ],
        [
            'short' => 'I',
            'label' => 'Iron',
            'value' => $resourceState?->iron ?? 0,
            'capacity' => $resourceState?->warehouse_capacity ?? 0,
            'capacity_label' => 'warehouse',
            'production' => $resourceState?->iron_production ?? 0,
            'usage_percent' => $resourceUsagePercent((int) ($resourceState?->iron ?? 0), (int) ($resourceState?->warehouse_capacity ?? 0)),
            'background' => '#78869c',
            'color' => '#f8fbff',
            'icon' => 'assets/res-icons/iron_small.png',
        ],
        [
            'short' => 'G',
            'label' => 'Crop',
            'value' => $resourceState?->crop ?? 0,
            'capacity' => $resourceState?->granary_capacity ?? 0,
            'capacity_label' => 'granary',
            'production' => $resourceState?->crop_production ?? 0,
            'usage_percent' => $resourceUsagePercent((int) ($resourceState?->crop ?? 0), (int) ($resourceState?->granary_capacity ?? 0)),
            'background' => '#ffd11d',
            'color' => '#342700',
            'icon' => 'assets/res-icons/crop_small.png',
        ],
    ];
    $warehouseResourceCards = array_slice($resourceCards, 0, 3);
    $granaryResourceCard = $resourceCards[3];
    $hasMovementSummary = $movementEntries->isNotEmpty()
        || ($incomingAttackCount > 0 && $movementEntries->where('kind', 'incoming_attack')->isEmpty() && ! $hasExpiredIncomingAttack)
        || ($incomingReinforcementCount > 0 && $movementEntries->where('kind', 'incoming_reinforcement')->isEmpty() && ! $hasExpiredIncomingReinforcement)
        || ($outgoingMovementCount > 0 && $movementEntries->where('kind', 'outgoing')->isEmpty() && ! $hasExpiredOutgoing);
@endphp

<div wire:key="village-row-{{ $village->id }}" class="relative" x-data="{ openControl: null }">
    <span class="absolute -left-4 top-5 h-px w-4 bg-[var(--color-line-strong)]"></span>

    <div class="grid gap-2 rounded-lg border {{ $isVillagePaused ? 'border-amber-400 bg-amber-50' : 'border-[var(--color-line)] bg-[var(--color-panel)]' }} px-3 py-2 shadow-sm xl:grid-cols-[minmax(10.5rem,0.75fr)_minmax(0,3.35fr)_auto] xl:items-center">
        <div class="min-w-0">
            <div class="flex min-w-0 items-center gap-2" title="{{ $village->name }} {{ $coordinateText }} · Pop {{ $populationText }}">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $statusDotClasses }}"
                    title="{{ $village->is_active ? 'Enabled' : 'Paused' }}"></span>
                <span class="min-w-0 truncate text-sm font-semibold {{ $villageNameClasses }}">{{ $village->name }}</span>
                <span class="shrink-0 text-xs font-semibold text-[var(--color-muted)]">{{ $coordinateText }}</span>
                <span class="shrink-0 text-xs font-semibold text-[var(--color-muted)]">Pop {{ $populationText }}</span>
            </div>

            <div class="relative mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-[var(--color-muted)]">
                <button type="button" @click="openControl = openControl === 'fields' ? null : 'fields'"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border text-[11px] font-extrabold transition {{ $controlButtonClasses($fieldsAutomationEnabled) }}"
                    title="F - Fields: enable, pause, and inspect resource field upgrades">
                    F
                </button>

                <button type="button" @click="openControl = openControl === 'buildings' ? null : 'buildings'"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border text-[11px] font-extrabold transition {{ $controlButtonClasses($buildingsAutomationEnabled) }}"
                    title="B - Buildings: enable, pause, and inspect village building upgrades">
                    B
                </button>

                <button type="button" @click="openControl = openControl === 'schedule' ? null : 'schedule'"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border text-[11px] font-extrabold transition {{ $controlButtonClasses($scheduleEntries->isNotEmpty()) }}"
                    title="S - Schedule: move orders to TOP or Hold them until they can run">
                    S
                </button>

                <button type="button" wire:click="toggleVillageHeroResources({{ $village->id }})"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border text-[11px] font-extrabold transition {{ $controlButtonClasses($heroResourcesEnabled) }}"
                    title="H - Hero resources: use stored hero resources before marketplace support">
                    H
                </button>

                <button type="button" wire:click="toggleVillageCelebrationAutomation({{ $village->id }})"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border text-[11px] font-extrabold transition {{ $controlButtonClasses($celebrationEnabled) }}"
                    title="C - Celebrations: start town hall celebrations when ready">
                    C
                </button>

                <button type="button" wire:click="toggleVillageTroopTrainingAutomation({{ $village->id }})"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border text-[11px] font-extrabold transition {{ $controlButtonClasses($troopTrainingEnabled) }}"
                    title="T - Troops: train enabled troop queues for this village">
                    T
                </button>

                <div x-cloak x-show="openControl === 'fields'" @click.outside="openControl = null" @keydown.escape.window="openControl = null"
                    class="absolute left-0 top-8 z-50 w-80 max-w-[calc(100vw-2rem)] rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-2 text-xs shadow-[0_18px_55px_rgba(15,23,42,0.18)]">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="font-semibold text-[var(--color-ink)]">Fields</span>
                        <button type="button" wire:click="toggleVillageFieldsAutomation({{ $village->id }})"
                            class="inline-flex items-center gap-2 rounded-full border px-1.5 py-1 text-[11px] font-semibold transition {{ $fieldsAutomationEnabled ? 'border-emerald-600/35 bg-emerald-500/10 text-emerald-800' : 'border-rose-600/30 bg-rose-500/10 text-rose-800' }}"
                            title="Enable or pause all field upgrades">
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full {{ $fieldsAutomationEnabled ? 'bg-emerald-500' : 'bg-rose-500' }}">
                                <span class="absolute inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-[10px] font-black shadow-sm transition {{ $fieldsAutomationEnabled ? 'right-0.5 text-emerald-700' : 'left-0.5 text-rose-700' }}">
                                    {{ $fieldsAutomationEnabled ? '✓' : '×' }}
                                </span>
                            </span>
                            {{ $fieldsAutomationEnabled ? 'On' : 'Off' }}
                        </button>
                    </div>

                    <div class="max-h-[70vh] space-y-1 overflow-y-auto pr-1">
                        @forelse ($fieldControls as $fieldControl)
                            <label wire:key="field-control-{{ $village->id }}-{{ $fieldControl['slot_id'] }}"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-[var(--color-panel-alt)]"
                                title="{{ $fieldControl['name'] }} level {{ $fieldControl['level'] }}">
                                <input type="checkbox" @checked($fieldControl['enabled'])
                                    wire:click="toggleVillageFieldSlotAutomation({{ $village->id }}, {{ $fieldControl['slot_id'] }})"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded bg-white ring-1 ring-black/5">
                                    @if ($fieldControl['icon'] !== null)
                                        <img src="{{ asset($fieldControl['icon']) }}" alt="" class="h-5 w-5 object-contain" />
                                    @else
                                        <span class="text-[10px] font-bold">{{ $fieldControl['gid'] }}</span>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1 truncate font-semibold text-[var(--color-ink)]">{{ $fieldControl['name'] }}</span>
                                <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[var(--color-muted)]">
                                    Lv {{ $fieldControl['level'] }}
                                </span>
                            </label>
                        @empty
                            <p class="px-2 py-3 text-[11px] font-semibold text-[var(--color-muted)]">No synced fields yet</p>
                        @endforelse
                    </div>
                </div>

                <div x-cloak x-show="openControl === 'buildings'" @click.outside="openControl = null" @keydown.escape.window="openControl = null"
                    class="absolute left-0 top-8 z-50 w-80 max-w-[calc(100vw-2rem)] rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-2 text-xs shadow-[0_18px_55px_rgba(15,23,42,0.18)]">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="font-semibold text-[var(--color-ink)]">Buildings</span>
                        <button type="button" wire:click="toggleVillageBuildingsAutomation({{ $village->id }})"
                            class="inline-flex items-center gap-2 rounded-full border px-1.5 py-1 text-[11px] font-semibold transition {{ $buildingsAutomationEnabled ? 'border-emerald-600/35 bg-emerald-500/10 text-emerald-800' : 'border-rose-600/30 bg-rose-500/10 text-rose-800' }}"
                            title="Enable or pause all building upgrades">
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full {{ $buildingsAutomationEnabled ? 'bg-emerald-500' : 'bg-rose-500' }}">
                                <span class="absolute inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-[10px] font-black shadow-sm transition {{ $buildingsAutomationEnabled ? 'right-0.5 text-emerald-700' : 'left-0.5 text-rose-700' }}">
                                    {{ $buildingsAutomationEnabled ? '✓' : '×' }}
                                </span>
                            </span>
                            {{ $buildingsAutomationEnabled ? 'On' : 'Off' }}
                        </button>
                    </div>

                    <div class="max-h-[70vh] space-y-1 overflow-y-auto pr-1">
                        @forelse ($buildingControls as $buildingControl)
                            <label wire:key="building-control-{{ $village->id }}-{{ $buildingControl['slot_id'] }}"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-[var(--color-panel-alt)]"
                                title="{{ $buildingControl['name'] }} level {{ $buildingControl['level'] }}">
                                <input type="checkbox" @checked($buildingControl['enabled'] && $buildingControl['target_enabled'])
                                    wire:click="toggleVillageBuildingSlotAutomation({{ $village->id }}, {{ $buildingControl['slot_id'] }})"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded bg-white ring-1 ring-black/5">
                                    @if ($buildingControl['icon'] !== null)
                                        <img src="{{ asset($buildingControl['icon']) }}" alt="" class="h-5 w-5 object-contain" />
                                    @else
                                        <span class="text-[10px] font-bold">{{ $buildingControl['gid'] }}</span>
                                    @endif
                                </span>
                                <span class="inline-flex min-w-0 flex-1 items-center gap-1.5">
                                    <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[var(--color-muted)]">
                                        #{{ $buildingControl['slot_id'] }}
                                    </span>
                                    <span class="min-w-0 truncate font-semibold text-[var(--color-ink)]">{{ $buildingControl['name'] }}</span>
                                </span>
                                <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[var(--color-muted)]">
                                    Lv {{ $buildingControl['level'] }}
                                </span>
                            </label>
                        @empty
                            <p class="px-2 py-3 text-[11px] font-semibold text-[var(--color-muted)]">No synced buildings yet</p>
                        @endforelse
                    </div>
                </div>

                <div x-cloak x-show="openControl === 'schedule'" @click.outside="openControl = null" @keydown.escape.window="openControl = null"
                    class="absolute left-0 top-8 z-50 w-96 max-w-[calc(100vw-2rem)] rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-2 text-xs shadow-[0_18px_55px_rgba(15,23,42,0.18)]">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="font-semibold text-[var(--color-ink)]">Schedule</span>
                        <span class="rounded-md bg-[var(--color-panel-alt)] px-2 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                            {{ $scheduleEntries->count() }} next
                        </span>
                    </div>

                    <div class="max-h-[70vh] space-y-1 overflow-y-auto pr-1">
                        @forelse ($scheduleEntries as $scheduleEntry)
                            <div wire:key="schedule-entry-{{ $village->id }}-{{ $scheduleEntry['key'] }}"
                                class="grid grid-cols-[auto_minmax(0,1fr)_auto_auto] items-center gap-2 rounded-md px-2 py-1.5 hover:bg-[var(--color-panel-alt)]"
                                title="{{ $scheduleEntry['name'] }} {{ $scheduleEntry['level_label'] }}">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-white text-[10px] font-extrabold ring-1 ring-black/5">
                                    @if ($scheduleEntry['icon'] !== null)
                                        <img src="{{ asset($scheduleEntry['icon']) }}" alt="" class="h-5 w-5 object-contain" />
                                    @else
                                        {{ $scheduleEntry['kind'] }}
                                    @endif
                                </span>
                                <span class="inline-flex min-w-0 items-center gap-1.5">
                                    @if (($scheduleEntry['kind'] ?? null) === 'B')
                                        <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[var(--color-muted)]">
                                            #{{ $scheduleEntry['slot_id'] }}
                                        </span>
                                    @endif
                                    <span class="min-w-0 truncate font-semibold text-[var(--color-ink)]">
                                        {{ $scheduleEntry['name'] }}
                                    </span>
                                    <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-1.5 py-0.5 font-mono text-[10px] font-extrabold text-[var(--color-muted)]">
                                        Lv {{ $scheduleEntry['level_label'] }}
                                    </span>
                                    @if ($scheduleEntry['reserve'] ?? false)
                                        <span class="rounded bg-sky-500/10 px-1.5 py-0.5 text-[9px] font-extrabold text-sky-900"
                                            title="Reserved building candidate outside the immediate field queue">
                                            B
                                        </span>
                                    @endif
                                </span>
                                <button type="button" wire:click="toggleVillageSchedulePin({{ $village->id }}, '{{ $scheduleEntry['key'] }}')"
                                    class="h-6 rounded-md border px-2 text-[10px] font-bold {{ $scheduleEntry['pinned'] ? 'border-emerald-700 bg-emerald-500 text-white' : 'border-[var(--color-line-strong)] text-[var(--color-muted)]' }}"
                                    title="Move this order to the front">
                                    TOP
                                </button>
                                <label class="inline-flex items-center gap-1 rounded-md border border-[var(--color-line-strong)] px-1.5 py-1 text-[10px] font-bold text-[var(--color-muted)]"
                                    title="Do not skip this order if it cannot run yet">
                                    <input type="checkbox" @checked($scheduleEntry['held'])
                                        wire:click="toggleVillageScheduleHold({{ $village->id }}, '{{ $scheduleEntry['key'] }}')"
                                        class="h-3 w-3 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                    Hold
                                </label>
                            </div>
                        @empty
                            <p class="px-2 py-3 text-[11px] font-semibold text-[var(--color-muted)]">No available scheduled orders</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="min-w-0 space-y-1.5 text-[11px]">
            <div class="grid min-w-0 grid-cols-1 items-center gap-1.5 sm:grid-cols-[minmax(0,1fr)_minmax(10rem,0.45fr)] xl:grid-cols-[minmax(28rem,1.55fr)_minmax(12rem,0.65fr)_minmax(13rem,1fr)]">
                <div class="grid min-w-0 overflow-hidden rounded-md border border-[#7b5a2e]/35 bg-[#d3b581] shadow-sm sm:grid-cols-[5.6rem_repeat(3,minmax(0,1fr))]"
                    title="Warehouse capacity {{ $warehouseCapacity }} shared by wood, clay, and iron">
                    <span class="inline-flex h-9 items-center justify-center gap-1 border-b border-[#7b5a2e]/30 px-1.5 font-mono text-[10px] font-extrabold text-[#3f2b12] sm:border-b-0 sm:border-r">
                        <img src="{{ asset('assets/buildings-icons/type10_small.png') }}" alt="Warehouse"
                            class="h-5 w-5 shrink-0 object-contain"
                            onerror="this.classList.add('hidden')" />
                        {{ $warehouseCapacity }}
                    </span>

                    @foreach ($warehouseResourceCards as $resourceCard)
                        <span class="relative inline-flex h-9 min-w-0 items-center gap-1.5 overflow-hidden border-t border-[#7b5a2e]/20 bg-white/95 px-1.5 pb-1.5 font-mono text-[10px] leading-none text-[var(--color-ink)] sm:border-l sm:border-t-0"
                            title="{{ $resourceCard['label'] }} stock: {{ $resourceCard['value'] }} of warehouse capacity {{ $warehouseCapacity }}. Production {{ $resourceCard['production'] >= 0 ? '+' : '' }}{{ $resourceCard['production'] }} per hour.">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded"
                                style="background-color: {{ $resourceCard['background'] }}; color: {{ $resourceCard['color'] }}">
                                <img src="{{ asset($resourceCard['icon']) }}" alt="{{ $resourceCard['short'] }}"
                                    class="h-4 w-4 shrink-0 object-contain"
                                    onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" />
                                <span class="hidden text-[9px] font-extrabold">{{ $resourceCard['short'] }}</span>
                            </span>
                            <span class="inline-flex min-w-0 flex-1 items-baseline justify-between gap-1 truncate">
                                <span class="shrink-0 text-[11px] font-extrabold text-[var(--color-ink)]">{{ $resourceCard['value'] }}</span>
                                <span class="shrink-0 font-extrabold {{ $resourceCard['production'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                    {{ $resourceCard['production'] >= 0 ? '+' : '' }}{{ $resourceCard['production'] }}/h
                                </span>
                            </span>
                            <span class="absolute bottom-1 left-8 right-1 h-1 overflow-hidden rounded-full bg-slate-200">
                                <span class="block h-full rounded-full"
                                    style="width: {{ $resourceCard['usage_percent'] }}%; background-color: {{ $resourceCard['background'] }}"></span>
                            </span>
                        </span>
                    @endforeach
                </div>

                <div class="grid min-w-0 overflow-hidden rounded-md border border-[#7b5a2e]/35 bg-[#d3b581] shadow-sm sm:grid-cols-[5.6rem_minmax(0,1fr)]"
                    title="Granary capacity {{ $granaryCapacity }} for crop">
                    <span class="inline-flex h-9 items-center justify-center gap-1 border-b border-[#7b5a2e]/30 px-1.5 font-mono text-[10px] font-extrabold text-[#3f2b12] sm:border-b-0 sm:border-r">
                        <img src="{{ asset('assets/buildings-icons/type11_small.png') }}" alt="Granary"
                            class="h-5 w-5 shrink-0 object-contain"
                            onerror="this.classList.add('hidden')" />
                        {{ $granaryCapacity }}
                    </span>

                    <span class="relative inline-flex h-9 min-w-0 items-center gap-1.5 overflow-hidden bg-white/95 px-1.5 pb-1.5 font-mono text-[10px] leading-none text-[var(--color-ink)]"
                        title="Crop stock: {{ $granaryResourceCard['value'] }} of granary capacity {{ $granaryCapacity }}. Production {{ $granaryResourceCard['production'] >= 0 ? '+' : '' }}{{ $granaryResourceCard['production'] }} per hour.">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded"
                            style="background-color: {{ $granaryResourceCard['background'] }}; color: {{ $granaryResourceCard['color'] }}">
                            <img src="{{ asset($granaryResourceCard['icon']) }}" alt="{{ $granaryResourceCard['short'] }}"
                                class="h-4 w-4 shrink-0 object-contain"
                                onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" />
                            <span class="hidden text-[9px] font-extrabold">{{ $granaryResourceCard['short'] }}</span>
                        </span>
                        <span class="inline-flex min-w-0 flex-1 items-baseline justify-between gap-1 truncate">
                            <span class="shrink-0 text-[11px] font-extrabold text-[var(--color-ink)]">{{ $granaryResourceCard['value'] }}</span>
                            <span class="shrink-0 font-extrabold {{ $granaryResourceCard['production'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ $granaryResourceCard['production'] >= 0 ? '+' : '' }}{{ $granaryResourceCard['production'] }}/h
                            </span>
                        </span>
                        <span class="absolute bottom-1 left-8 right-1 h-1 overflow-hidden rounded-full bg-slate-200">
                            <span class="block h-full rounded-full"
                                style="width: {{ $granaryResourceCard['usage_percent'] }}%; background-color: {{ $granaryResourceCard['background'] }}"></span>
                        </span>
                    </span>
                </div>

                <span class="inline-flex h-9 w-full min-w-0 items-center gap-1 overflow-hidden rounded-md bg-[var(--color-panel-alt)] px-1.5 font-mono text-[10px] font-semibold text-[var(--color-muted)]"
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
                        $constructionRecordedAtTimestamp = $entryRecordedAtTimestamp($constructionEntry);
                        $constructionRemainingNow = $constructionRemainingSeconds !== null
                            ? max(0, $constructionRemainingSeconds - max(0, now()->getTimestamp() - $constructionRecordedAtTimestamp))
                            : null;
                        $constructionInitialClock = $formatClock(
                            $constructionRemainingNow !== null
                                ? $constructionRemainingNow
                                : null,
                        );
                        $constructionEndsAtTimestamp = $constructionRemainingSeconds !== null
                            ? ($constructionRecordedAtTimestamp + $constructionRemainingSeconds) * 1000
                            : null;
                        $constructionFinishLabel = !empty($constructionEntry['finish_label'])
                            ? trim((string) $constructionEntry['finish_label'])
                            : null;
                        if ($constructionFinishLabel === '00:00') {
                            $constructionFinishLabel = null;
                        }
                    @endphp
                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-md border px-2 py-1 font-semibold shadow-sm"
                        style="background-color: #f88c1f; border-color: rgba(248, 140, 31, 0.55); color: #2f1600;"
                        @if ($constructionRemainingSeconds !== null) x-data="{
                                endsAt: {{ $constructionEndsAtTimestamp }},
                                remaining: 0,
                                syncQueued: false,
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
                                    if (this.remaining <= 0 && !this.syncQueued) {
                                        this.syncQueued = true;
                                        $wire.queueVillageTimerSync({{ $village->id }});
                                    }
                                },
                                expired() {
                                    return this.remaining <= 0;
                                },
                                formatted() {
                                    const hours = String(Math.floor(this.remaining / 3600)).padStart(1, '0');
                                    const minutes = String(Math.floor((this.remaining % 3600) / 60)).padStart(2, '0');
                                    const seconds = String(this.remaining % 60).padStart(2, '0');

                                    return `${hours}:${minutes}:${seconds}`;
                                }
                            }" @endif>
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
                        @if ($constructionFinishLabel !== null)
                            <span class="font-mono text-[11px]">Ends {{ $constructionFinishLabel }}</span>
                        @endif
                        @if ($constructionRemainingSeconds !== null)
                            <span class="rounded bg-white/45 px-1 text-[10px] font-extrabold uppercase"
                                x-show="expired()" x-cloak>Sync due</span>
                        @elseif ($constructionRemainingNow === 0)
                            <span class="rounded bg-white/45 px-1 text-[10px] font-extrabold uppercase">Sync due</span>
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
                        $movementRecordedAtTimestamp = $entryRecordedAtTimestamp($movementEntry);
                        $movementEndsAtTimestamp = $movementRemainingSeconds !== null
                            ? ($movementRecordedAtTimestamp + $movementRemainingSeconds) * 1000
                            : null;
                        $movementDisplay = $movementVisual($movementKind, $movementLabel);
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 font-semibold shadow-sm"
                        style="{{ $movementDisplay['style'] }}"
                        @if ($movementRemainingSeconds !== null) x-data="{
                                endsAt: {{ $movementEndsAtTimestamp }},
                                remaining: 0,
                                syncQueued: false,
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
                                    if (this.remaining <= 0 && !this.syncQueued) {
                                        this.syncQueued = true;
                                        $wire.queueVillageTimerSync({{ $village->id }});
                                    }
                                },
                                expired() {
                                    return this.remaining <= 0;
                                },
                                formatted() {
                                    const hours = String(Math.floor(this.remaining / 3600)).padStart(1, '0');
                                    const minutes = String(Math.floor((this.remaining % 3600) / 60)).padStart(2, '0');
                                    const seconds = String(this.remaining % 60).padStart(2, '0');

                                    return `${hours}:${minutes}:${seconds}`;
                                }
                            }"
                            @endif>
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
                        @if ($movementRemainingSeconds !== null)
                            <span class="rounded bg-white/45 px-1 text-[10px] font-extrabold uppercase"
                                x-show="expired()" x-cloak>Sync due</span>
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
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-sm font-semibold transition {{ $village->is_active ? 'border-[var(--color-line-strong)] hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]' : 'border-emerald-700/25 bg-emerald-500/10 text-emerald-800 hover:bg-emerald-500/20' }}"
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
