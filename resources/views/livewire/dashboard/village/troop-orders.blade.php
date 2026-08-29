@php
    $trainableUnits = collect($units)
        ->where('trainable', true)
        ->map(fn (array $unit): array => [
            'unit_id' => $unit['unit_id'],
            'english' => $unit['english'],
            'arabic' => $unit['arabic'],
            'icon_url' => asset($unit['icon_path']),
            'smithy_level' => $unit['smithy_level'],
            'max_trainable' => $unit['max_trainable'],
        ])->values()->all();
    $firstTrainableUnitId = $trainableUnits[0]['unit_id'] ?? null;
    $smithyUnits = collect($units)->where('research_state', 'researched')->values();
    $unavailableUnits = collect($units)->where('trainable', false)->values();
@endphp

<div class="grid gap-3" @if ($refreshRequestedAt !== null || $hasActiveOrders) wire:poll.3s.visible="pollTroopView" @endif>
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-2">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-[var(--color-ink)]">Troop orders</h3>
            <p class="text-[11px] text-[var(--color-muted)]">
                {{ $snapshotAt ? 'Updated '.$snapshotAt->diffForHumans() : 'Troop information has not been loaded yet.' }}
            </p>
        </div>
        <button type="button" wire:click="refreshMilitaryState" wire:loading.attr="disabled" wire:target="refreshMilitaryState"
            aria-label="Refresh military state" title="Read the latest training buildings and Smithy state from Travian"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[var(--color-line-strong)] bg-[var(--color-panel)] text-[var(--color-ink)] hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] disabled:opacity-50"><x-refresh-icon wire:loading.class="animate-spin" wire:target="refreshMilitaryState" /></button>
    </div>

    @if ($message !== '')
        <p class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-2 text-xs text-[var(--color-ink)]">{{ $message }}</p>
    @endif

    <div class="grid gap-3 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-start">
    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] p-3"
        x-data="{
            units: @js($trainableUnits), selectedUnitId: @js($firstTrainableUnitId), quantity: 1, open: false, submitting: false,
            get selected() { return this.units.find((unit) => unit.unit_id === Number(this.selectedUnitId)) ?? null },
            submit() {
                if (! this.selected || Number(this.quantity) < 1 || this.submitting) return;
                this.submitting = true;
                this.$wire.queueTraining(Number(this.selectedUnitId), Number(this.quantity))
                    .then(() => { this.quantity = 1 })
                    .finally(() => { this.submitting = false });
            },
        }">
        <div class="mb-2">
            <h4 class="text-xs font-semibold text-[var(--color-ink)]">Train troops</h4>
        </div>
        <form @submit.prevent="submit" class="grid gap-2 sm:grid-cols-[minmax(11rem,1fr)_4.5rem_7.25rem] sm:items-end">
            <div class="relative grid gap-1 text-xs font-semibold text-[var(--color-muted)]" @click.outside="open = false">
                <span>Troop to train</span>
                <button type="button" @click="open = ! open" :aria-expanded="open"
                    class="flex min-h-10 w-full items-center gap-2 rounded-md border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-2.5 py-1.5 text-left text-sm text-[var(--color-ink)]">
                    <template x-if="selected"><img :src="selected.icon_url" alt="" class="h-7 w-7 rounded bg-white object-contain p-0.5 ring-1 ring-black/5"></template>
                    <span class="min-w-0 flex-1 truncate" x-text="selected ? `${selected.arabic} · ${selected.english}` : 'Choose a trainable unit'"></span>
                    <span aria-hidden="true" class="text-[10px]">▾</span>
                </button>
                <div x-cloak x-show="open" x-transition.origin.top class="absolute inset-x-0 top-full z-30 mt-1 max-h-60 overflow-y-auto rounded-md border border-[var(--color-line-strong)] bg-[var(--color-panel)] p-1 shadow-xl">
                    <template x-for="unit in units" :key="unit.unit_id">
                        <button type="button" @click="selectedUnitId = unit.unit_id; open = false" class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left hover:bg-[var(--color-panel-alt)]">
                            <img :src="unit.icon_url" alt="" class="h-8 w-8 rounded bg-white object-contain p-0.5 ring-1 ring-black/5">
                            <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-[var(--color-ink)]" x-text="unit.arabic"></span><span class="block truncate text-[11px] font-normal text-[var(--color-muted)]" x-text="`${unit.english} · ${unit.max_trainable} available`"></span></span>
                        </button>
                    </template>
                    <p x-show="units.length === 0" class="px-2 py-2 text-[11px] font-normal text-[var(--color-muted)]">No unit is ready to train. Refresh after checking its training building.</p>
                </div>
                @error('unitId') <span class="text-[11px] text-rose-700">{{ $message }}</span> @enderror
            </div>
            <label class="grid gap-1 text-xs font-semibold text-[var(--color-muted)]">
                Quantity
                <input type="number" min="1" max="9999" x-model.number="quantity" class="rounded-md border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-2.5 py-2 text-sm text-[var(--color-ink)]" />
                @error('quantity') <span class="text-[11px] text-rose-700">{{ $message }}</span> @enderror
            </label>
            <button type="submit" :disabled="! selected || quantity < 1 || submitting" title="Schedule this training order"
                class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-[var(--color-accent)] bg-[var(--color-accent)] px-3 text-xs font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:-translate-y-px hover:brightness-105 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-45 disabled:hover:translate-y-0">
                <svg x-show="! submitting" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                    <path d="M12 8v4l2.75 1.75M18.5 5.5l1.5-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span x-show="! submitting">Schedule</span><span x-cloak x-show="submitting">Scheduling…</span>
            </button>
        </form>
        <p class="mt-2 text-[11px] leading-4 text-[var(--color-muted)]">You can cancel for one minute. The final quantity depends on the available resources.</p>
    </section>

        <section class="overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)]"
            x-data="{
                now: Date.now(), timer: null,
                init() { this.timer = setInterval(() => { this.now = Date.now() }, 1000) }, destroy() { clearInterval(this.timer) },
                countdown(iso) { const seconds = Math.max(0, Math.ceil((new Date(iso).getTime() - this.now) / 1000)); return seconds > 0 ? `Sending in ${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}` : 'Sending…' },
                localTime(iso) { return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'medium' }).format(new Date(iso)) },
            }">
            <div class="flex items-center justify-between gap-2 border-b border-[var(--color-line)] px-3 py-2">
                <span class="text-xs font-semibold text-[var(--color-ink)]">Order history</span>
                <span class="text-[10px] text-[var(--color-muted)]">Latest 30 orders</span>
            </div>
            <div class="max-h-64 divide-y divide-[var(--color-line)] overflow-y-auto">
                @if ($orders->isEmpty())
                    <p class="px-3 py-6 text-center text-[11px] text-[var(--color-muted)]">No troop or Smithy orders yet.</p>
                @endif
                @foreach ($orders as $order)
                    @php
                        $orderUnit = collect($units)->firstWhere('unit_id', $order->unit_id);
                        $statusVisual = match ($order->status->value) {
                            'submitted' => ['Sent', 'bg-emerald-100 text-emerald-800 ring-emerald-600/20'],
                            'cancelled' => ['Cancelled', 'bg-slate-200 text-slate-700 ring-slate-500/20'],
                            'failed' => ['Failed', 'bg-rose-100 text-rose-800 ring-rose-600/20'],
                            'claimed' => ['Sending', 'bg-sky-100 text-sky-800 ring-sky-600/20'],
                            'waiting_resources' => ['Waiting for resources', 'bg-amber-100 text-amber-900 ring-amber-600/20'],
                            default => ['Waiting to send', 'bg-violet-100 text-violet-800 ring-violet-600/20'],
                        };
                        $partiallyAccepted = $order->order_type === 'training'
                            && $order->accepted_quantity !== null
                            && $order->accepted_quantity < $order->requested_quantity;
                    @endphp
                    <article wire:key="troop-order-{{ $order->id }}" class="grid grid-cols-[1.75rem_minmax(0,1fr)_auto] gap-2 px-3 py-1.5 text-xs">
                        <img src="{{ asset($orderUnit['icon_path'] ?? 'assets/troops-icons/u'.$order->unit_id.'.png') }}" alt="" class="h-7 w-7 rounded bg-white object-contain p-0.5 ring-1 ring-black/5" />
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <span class="font-semibold text-[var(--color-ink)]">{{ $orderUnit['english'] ?? 'Unit '.$order->unit_id }}</span>
                                <span class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide {{ $order->order_type === 'smithy' ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $order->order_type === 'smithy' ? 'Improvement · Lv '.$order->target_level : 'Training · ×'.$order->requested_quantity }}</span>
                                @if ($order->use_hero_resources)<span class="rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-900">Use Hero resources if needed</span>@endif
                                <span class="rounded px-1.5 py-0.5 text-[9px] font-bold ring-1 {{ $statusVisual[1] }}">{{ $statusVisual[0] }}</span>
                                @if ($order->accepted_quantity !== null && $order->order_type === 'training')
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-bold {{ $partiallyAccepted ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">Travian accepted {{ $order->accepted_quantity }} of {{ $order->requested_quantity }}</span>
                                @endif
                            </div>
                            @if ($order->result_message)
                                <p class="mt-1 leading-4 {{ $order->status->value === 'failed' ? 'font-medium text-rose-700' : ($partiallyAccepted ? 'font-medium text-amber-800' : 'text-[var(--color-muted)]') }}">{{ $order->result_message }}</p>
                            @endif
                            <p class="mt-1 text-[10px] text-[var(--color-muted)]">
                                Scheduled <time x-text="localTime(@js($order->created_at->toIso8601String()))"></time>
                                @if ($order->submitted_at) · Sent <time x-text="localTime(@js($order->submitted_at->toIso8601String()))"></time> @endif
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            @if ($order->status->value === 'scheduled')
                                <span class="tabular-nums text-[10px] text-[var(--color-muted)]" x-text="countdown(@js($order->execute_after->toIso8601String()))"></span>
                                <button type="button" wire:click="cancelOrder({{ $order->id }})" class="rounded border border-rose-600/35 px-2 py-1 font-semibold text-rose-700 hover:bg-rose-500/10">Cancel</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div><h4 class="text-xs font-semibold text-[var(--color-ink)]">Smithy improvements</h4><p class="text-[10px] text-[var(--color-muted)]">One level per order, with the same one-minute cancellation window.</p></div>
            @if ($smithyQueue !== [])
                <span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-900">Improvement in progress</span>
            @endif
        </div>

        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @forelse ($smithyUnits as $unit)
                @php
                    $smithyCanQueue = $smithyQueue === []
                        && $unit['smithy_level'] < 20
                        && ($unit['smithy_actionable'] || $unit['smithy_resource_shortage']);
                    $smithyAtBuildingLimit = $smithyQueue === []
                        && $unit['smithy_level'] >= $unit['smithy_building_level']
                        && $unit['smithy_building_level'] > 0
                        && $unit['smithy_level'] < 20;
                    $smithyCompleted = $unit['smithy_level'] >= 20;
                @endphp
                <article wire:key="smithy-unit-{{ $unit['unit_id'] }}" class="rounded-lg border border-indigo-200 bg-[var(--color-panel)] p-2.5"
                    x-data="{ useHeroResources: false, submitting: false }">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset($unit['icon_path']) }}" alt="{{ $unit['english'] }}" class="h-9 w-9 rounded bg-white object-contain p-0.5 ring-1 ring-black/5" />
                        <div class="min-w-0 flex-1">
                            <h5 class="truncate text-xs font-semibold text-[var(--color-ink)]">{{ $unit['arabic'] }} · {{ $unit['english'] }}</h5>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px]">
                                <span class="rounded bg-indigo-100 px-1.5 py-0.5 font-bold text-indigo-800">Lv {{ $unit['smithy_level'] }}</span>
                                @if ($unit['smithy_duration_seconds'] > 0)<span class="text-[var(--color-muted)]">Next · {{ gmdate($unit['smithy_duration_seconds'] >= 3600 ? 'G:i:s' : 'i:s', $unit['smithy_duration_seconds']) }}</span>@endif
                            </div>
                        </div>
                        <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[9px] font-bold text-indigo-800">SMITHY</span>
                    </div>

                    @if (collect($unit['smithy_cost'])->filter(fn ($amount): bool => (int) $amount > 0)->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1.5 text-[10px]" aria-label="Resources required for level {{ $unit['smithy_level'] + 1 }}">
                            @foreach (['wood' => ['Wood', 'assets/res-icons/lumber_small.png'], 'clay' => ['Clay', 'assets/res-icons/clay_small.png'], 'iron' => ['Iron', 'assets/res-icons/iron_small.png'], 'crop' => ['Crop', 'assets/res-icons/crop_small.png']] as $resourceKey => [$resourceLabel, $resourceIcon])
                                <span wire:key="smithy-cost-{{ $unit['unit_id'] }}-{{ $resourceKey }}" class="inline-flex items-center gap-1 rounded bg-[var(--color-panel-alt)] px-1.5 py-1 text-[var(--color-muted)]"><img src="{{ asset($resourceIcon) }}" alt="{{ $resourceLabel }}" title="{{ $resourceLabel }}" class="h-4 w-4 object-contain" /><strong class="tabular-nums text-[var(--color-ink)]">{{ number_format((int) ($unit['smithy_cost'][$resourceKey] ?? 0)) }}</strong></span>
                            @endforeach
                        </div>
                    @endif

                    @if ($smithyCanQueue)
                        @if ($unit['smithy_resource_shortage'])
                            <p class="mt-2 rounded-md border border-amber-300 bg-amber-50 px-2 py-1.5 text-[10px] font-medium leading-4 text-amber-900">Village resources are insufficient. You may cover the missing amount from the Hero inventory.</p>
                        @endif
                        <label class="mt-2 flex items-start gap-1.5 text-[10px] leading-4 text-[var(--color-muted)]">
                            <input type="checkbox" x-model="useHeroResources" class="mt-0.5 h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-indigo-600 focus:ring-indigo-500" />
                            Use Hero resources for the missing amount
                        </label>
                        <button type="button" @click="submitting = true; $wire.queueSmithyUpgrade({{ $unit['unit_id'] }}, useHeroResources).finally(() => submitting = false)" :disabled="submitting || (@js($unit['smithy_resource_shortage']) && ! useHeroResources)"
                            class="mt-2 w-full rounded-md bg-indigo-600 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"><span x-show="! submitting">{{ $unit['smithy_resource_shortage'] ? 'Use hero resources & improve to Lv '.($unit['smithy_level'] + 1) : 'Improve to Lv '.($unit['smithy_level'] + 1) }}</span><span x-cloak x-show="submitting">Queuing…</span></button>
                    @else
                        <p class="mt-2 rounded bg-[var(--color-panel-alt)] px-2 py-1.5 text-[10px] leading-4 text-[var(--color-muted)]">
                            {{ $smithyCompleted ? 'Improvement complete · maximum level 20 reached.' : ($smithyQueue !== [] ? 'Smithy improvement in progress.' : ($smithyAtBuildingLimit ? 'Expand the Smithy to unlock the next improvement level.' : ($unit['smithy_message'] ?: 'The next improvement is unavailable. Check the Smithy, then refresh the troop information.'))) }}
                        </p>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-[var(--color-line-strong)] px-3 py-2 text-[11px] text-[var(--color-muted)] sm:col-span-2">No researched unit is currently available to the Smithy.</p>
            @endforelse
        </div>
    </section>

    @if ($unavailableUnits->isNotEmpty())
        <section class="rounded-lg border border-amber-300 bg-amber-50/70 p-3">
            <div><h4 class="text-xs font-semibold text-amber-950">Not trainable yet</h4><p class="text-[10px] text-amber-900/75">These units are separated from trainable troops. Each row explains the next step.</p></div>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($unavailableUnits as $unit)
                    <article wire:key="unavailable-unit-{{ $unit['unit_id'] }}" class="rounded-lg border {{ $unit['status_tone'] === 'blocked' ? 'border-rose-300 bg-rose-50' : 'border-amber-300 bg-white' }} p-2.5">
                        <div class="flex items-start gap-2">
                            <img src="{{ asset($unit['icon_path']) }}" alt="{{ $unit['english'] }}" class="h-9 w-9 rounded bg-white object-contain p-0.5 ring-1 ring-black/5" />
                            <div class="min-w-0 flex-1"><h5 class="truncate text-xs font-semibold text-[var(--color-ink)]">{{ $unit['arabic'] }} · {{ $unit['english'] }}</h5><span class="mt-1 inline-flex rounded-full {{ $unit['status_tone'] === 'blocked' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-900' }} px-2 py-0.5 text-[9px] font-bold">{{ $unit['status_label'] }}</span></div>
                        </div>
                        <p class="mt-2 text-[10px] leading-4 text-[var(--color-muted)]">{{ $unit['status_help'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
