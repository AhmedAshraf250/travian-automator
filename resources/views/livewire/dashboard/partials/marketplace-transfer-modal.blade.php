@if ($showMarketplaceTransferModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-[var(--color-line)] px-5 py-3.5">
                <div>
                    <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Village trade</p>
                    <h2 class="mt-0.5 font-[var(--font-display)] text-xl font-semibold">TR - Trade</h2>
                    @if ($marketplaceSourceVillageLabel !== '')
                        <p class="mt-1 inline-flex rounded-md bg-[var(--color-panel-alt)] px-2 py-1 text-xs font-semibold text-[var(--color-muted)]">
                            Sending from {{ $marketplaceSourceVillageLabel }}
                        </p>
                    @endif
                </div>

                <button type="button" wire:click="closeMarketplaceTransferModal"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="border-b border-[var(--color-line)] px-5 pt-3">
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" wire:click="setMarketplaceTransferTab('send')"
                        class="rounded-t-lg border px-3 py-2 text-sm font-semibold transition {{ $marketplaceTransferTab === 'send' ? 'border-[var(--color-line)] border-b-[var(--color-panel)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-transparent bg-[var(--color-panel-alt)] text-[var(--color-muted)] hover:text-[var(--color-accent)]' }}">
                        Quick Send
                    </button>

                    <button type="button" wire:click="setMarketplaceTransferTab('settings')"
                        class="rounded-t-lg border px-3 py-2 text-sm font-semibold transition {{ $marketplaceTransferTab === 'settings' ? 'border-[var(--color-line)] border-b-[var(--color-panel)] bg-[var(--color-panel)] text-[var(--color-accent)]' : 'border-transparent bg-[var(--color-panel-alt)] text-[var(--color-muted)] hover:text-[var(--color-accent)]' }}">
                        Trade Settings
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden px-5 py-4">
                @if ($marketplaceTransferTab === 'send')
                    @php
                        $availableMerchants = $marketplaceTransferCapacity['available_merchants'] ?? null;
                        $merchantCapacity = (int) ($marketplaceTransferCapacity['merchant_capacity'] ?? 500);
                        $resourceStep = max(1, $merchantCapacity);
                        $totalCapacity = $marketplaceTransferCapacity['total_capacity'] ?? null;
                        $currentResources = $marketplaceTransferCapacity['resources'] ?? [];
                        $snapshotAge = $marketplaceTransferCapacity['reported_at'] ?? null;
                        $enteredTotal = max(0, (int) $marketplaceWoodDraft) + max(0, (int) $marketplaceClayDraft) + max(0, (int) $marketplaceIronDraft) + max(0, (int) $marketplaceCropDraft);
                        $draftAmounts = [
                            'marketplaceWoodDraft' => max(0, (int) $marketplaceWoodDraft),
                            'marketplaceClayDraft' => max(0, (int) $marketplaceClayDraft),
                            'marketplaceIronDraft' => max(0, (int) $marketplaceIronDraft),
                            'marketplaceCropDraft' => max(0, (int) $marketplaceCropDraft),
                        ];
                        $resourceMaxByModel = [];

                        foreach ($draftAmounts as $draftModel => $draftAmount) {
                            $resourceMaxByModel[$draftModel] = $totalCapacity !== null
                                ? max(0, $draftAmount + ((int) $totalCapacity - $enteredTotal))
                                : null;

                            $stock = $currentResources[match ($draftModel) {
                                'marketplaceWoodDraft' => 'wood',
                                'marketplaceClayDraft' => 'clay',
                                'marketplaceIronDraft' => 'iron',
                                default => 'crop',
                            }] ?? null;

                            if ($stock !== null) {
                                $resourceMaxByModel[$draftModel] = $resourceMaxByModel[$draftModel] !== null
                                    ? min((int) $resourceMaxByModel[$draftModel], max(0, (int) $stock))
                                    : max(0, (int) $stock);
                            }
                        }
                    @endphp
                    <section class="rounded-lg border border-sky-600/20 bg-sky-500/10 px-4 py-3">
                        <div class="flex flex-col gap-1">
                            <h3 class="text-sm font-semibold text-sky-950">Quick Send uses saved Trade Settings</h3>
                            <p class="text-xs leading-5 text-sky-900">
                                This tab sends resources now. The saved limits and travel-time policy live in Trade Settings here and in the village Trading tab.
                            </p>
                        </div>
                    </section>

                    <section
                        @if ($marketplaceSnapshotRefreshPollUntil !== null && now()->getTimestamp() <= $marketplaceSnapshotRefreshPollUntil) wire:poll.3s="refreshMarketplaceTransferCapacityView" @endif
                        class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-4 py-3">
                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Merchant capacity</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if ($totalCapacity !== null)
                                        <span class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-xs font-semibold text-[var(--color-ink)]">
                                            {{ $availableMerchants }} merchant(s)
                                        </span>
                                        <span class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-xs font-semibold text-[var(--color-muted)]">
                                            {{ $merchantCapacity }} each
                                        </span>
                                        <span class="rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                            Can send {{ $totalCapacity }}
                                        </span>
                                    @else
                                        <span class="rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                            No merchant snapshot
                                        </span>
                                        <span class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-xs font-semibold text-[var(--color-muted)]">
                                            {{ $merchantCapacity }} resources per merchant
                                        </span>
                                    @endif
                                    @if ($snapshotAge !== null)
                                        <span class="rounded-md bg-[var(--color-panel)] px-2.5 py-1 text-xs font-semibold text-[var(--color-muted)]">
                                            Updated {{ $snapshotAge }}
                                        </span>
                                    @endif
                                </div>
                                @if ($totalCapacity === null)
                                    <p class="mt-2 text-[11px] leading-4 text-[var(--color-muted)]">
                                        Use Refresh to check available merchants in the background.
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <button type="button" wire:click="refreshMarketplaceSnapshot" wire:loading.attr="disabled" wire:target="refreshMarketplaceSnapshot"
                                    class="inline-flex h-8 items-center justify-center rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] px-3 text-xs font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                                    <span wire:loading.remove wire:target="refreshMarketplaceSnapshot">Refresh</span>
                                    <span wire:loading wire:target="refreshMarketplaceSnapshot">Checking...</span>
                                </button>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $totalCapacity !== null && $enteredTotal > $totalCapacity ? 'bg-rose-500/10 text-rose-800' : 'bg-[var(--color-panel)] text-[var(--color-muted)]' }}">
                                    Total entered:
                                    {{ $enteredTotal }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-2 rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-4 py-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            'wood' => ['Wood', 'assets/res-icons/lumber_small.png'],
                            'clay' => ['Clay', 'assets/res-icons/clay_small.png'],
                            'iron' => ['Iron', 'assets/res-icons/iron_small.png'],
                            'crop' => ['Crop', 'assets/res-icons/crop_small.png'],
                        ] as $resourceKey => [$resourceLabel, $resourceIcon])
                            <span class="inline-flex items-center justify-between gap-2 rounded-md bg-[var(--color-panel)] px-2.5 py-2 text-xs font-semibold text-[var(--color-muted)]">
                                <span class="inline-flex items-center gap-1.5">
                                    <img src="{{ asset($resourceIcon) }}" alt="" class="h-4 w-4 object-contain" />
                                    {{ $resourceLabel }}
                                </span>
                                <span class="font-mono text-[var(--color-ink)]">{{ $currentResources[$resourceKey] ?? 'unknown' }}</span>
                            </span>
                        @endforeach
                    </section>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-semibold text-[var(--color-ink)]">Destination</span>
                            <select wire:model.live="marketplaceDestinationMode"
                                class="h-10 min-w-0 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 text-sm outline-none focus:border-[var(--color-accent)]">
                                <option value="owned">Owned village</option>
                                <option value="manual">Manual coordinates</option>
                            </select>
                        </label>

                        @if ($marketplaceDestinationMode === 'owned')
                            <label class="grid gap-1 text-sm">
                                <span class="font-semibold text-[var(--color-ink)]">Village</span>
                                <select wire:model.live="marketplaceDestinationVillageId"
                                    class="h-10 min-w-0 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 text-sm outline-none focus:border-[var(--color-accent)]">
                                    <option value="">Choose village</option>
                                    @foreach ($marketplaceTransferVillages as $destinationVillage)
                                        <option value="{{ $destinationVillage->id }}">
                                            {{ $destinationVillage->name }} [{{ $destinationVillage->x }}|{{ $destinationVillage->y }}]
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @else
                            <div class="grid grid-cols-2 gap-2">
                                <label class="grid gap-1 text-sm">
                                    <span class="font-semibold text-[var(--color-ink)]">X</span>
                                    <input type="number" wire:model.live="marketplaceDestinationX"
                                        class="h-10 min-w-0 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 text-sm outline-none focus:border-[var(--color-accent)]">
                                </label>
                                <label class="grid gap-1 text-sm">
                                    <span class="font-semibold text-[var(--color-ink)]">Y</span>
                                    <input type="number" wire:model.live="marketplaceDestinationY"
                                        class="h-10 min-w-0 rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 text-sm outline-none focus:border-[var(--color-accent)]">
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            'marketplaceWoodDraft' => ['Wood', 'assets/res-icons/lumber_small.png'],
                            'marketplaceClayDraft' => ['Clay', 'assets/res-icons/clay_small.png'],
                            'marketplaceIronDraft' => ['Iron', 'assets/res-icons/iron_small.png'],
                            'marketplaceCropDraft' => ['Crop', 'assets/res-icons/crop_small.png'],
                        ] as $resourceModel => [$resourceLabel, $resourceIcon])
                            <label class="grid gap-1 text-sm">
                                <span class="inline-flex items-center gap-1 font-semibold text-[var(--color-ink)]">
                                    <img src="{{ asset($resourceIcon) }}" alt="" class="h-4 w-4 object-contain" />
                                    {{ $resourceLabel }}
                                </span>
                                <div class="grid h-10 grid-cols-[2.5rem_minmax(0,1fr)_2.5rem] overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] focus-within:border-[var(--color-accent)]">
                                    <button type="button" wire:click="adjustMarketplaceResourceDraft('{{ match ($resourceModel) { 'marketplaceWoodDraft' => 'wood', 'marketplaceClayDraft' => 'clay', 'marketplaceIronDraft' => 'iron', default => 'crop' } }}', -1)"
                                        class="inline-flex items-center justify-center border-r border-[var(--color-line)] text-base font-black text-[var(--color-muted)] transition hover:bg-[var(--color-panel)] hover:text-[var(--color-accent)]"
                                        title="Decrease {{ $resourceLabel }}">
                                        -
                                    </button>
                                    <input type="number" min="0" step="{{ $resourceStep }}"
                                        @if (($resourceMaxByModel[$resourceModel] ?? null) !== null) max="{{ $resourceMaxByModel[$resourceModel] }}" @endif
                                        wire:model.live="{{ $resourceModel }}"
                                        class="min-w-0 border-0 bg-transparent px-3 text-center font-mono text-sm outline-none focus:ring-0">
                                    <button type="button" wire:click="adjustMarketplaceResourceDraft('{{ match ($resourceModel) { 'marketplaceWoodDraft' => 'wood', 'marketplaceClayDraft' => 'clay', 'marketplaceIronDraft' => 'iron', default => 'crop' } }}', 1)"
                                        class="inline-flex items-center justify-center border-l border-[var(--color-line)] text-base font-black text-[var(--color-muted)] transition hover:bg-[var(--color-panel)] hover:text-[var(--color-accent)]"
                                        title="Increase {{ $resourceLabel }}">
                                        +
                                    </button>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-4">
                        <div class="mb-4 rounded-lg border border-sky-600/20 bg-sky-500/10 px-3 py-2">
                            <h3 class="text-sm font-semibold text-sky-950">Shared village Trade Settings</h3>
                            <p class="mt-1 text-xs leading-5 text-sky-900">
                                These are the same saved settings shown in the village Trading tab. They guide automatic support and protect manual TR sends.
                            </p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-3">
                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Allow this village to send surplus resources to other villages.">
                                <input type="checkbox" wire:model.live="villageSendResourcesDraft"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                Send resources
                            </label>

                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Allow other villages to supply this village when it needs resources.">
                                <input type="checkbox" wire:model.live="villageSupplyResourcesDraft"
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
                                Receive support
                            </label>

                            <label class="flex items-center gap-2 rounded-lg bg-[var(--color-panel)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]"
                                title="Allow crop support when this village has negative crop production.">
                                <input type="checkbox" wire:model.live="villageSupplyNegativeCropDraft"
                                    @disabled(! $villageSupplyResourcesDraft)
                                    class="h-3.5 w-3.5 rounded border-[var(--color-line-strong)] text-[var(--color-accent)] disabled:opacity-50 focus:ring-[var(--color-accent)]" />
                                Negative crop support
                            </label>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Minimum stock before sending</span>
                                <div class="flex h-10 items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="0" max="100"
                                        wire:model.live="villageSendMinResourcePercentageDraft"
                                        @disabled(! $villageSendResourcesDraft)
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-[var(--color-ink)] outline-none disabled:opacity-50 focus:ring-0" />
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">%</span>
                                </div>
                                @error('villageSendMinResourcePercentageDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium text-[var(--color-ink)]">Reserve after sending</span>
                                <div class="flex h-10 items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="0" max="100"
                                        wire:model.live="villageSendReserveResourcePercentageDraft"
                                        @disabled(! $villageSendResourcesDraft)
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-[var(--color-ink)] outline-none disabled:opacity-50 focus:ring-0" />
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">%</span>
                                </div>
                                @error('villageSendReserveResourcePercentageDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="grid gap-1 text-sm md:col-span-2">
                                <span class="font-medium text-[var(--color-ink)]">Max one-way merchant travel time</span>
                                <div class="flex h-10 items-center overflow-hidden rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] focus-within:border-[var(--color-accent)]">
                                    <input type="number" min="1" max="10080"
                                        wire:model.live="villageTradeMaxDurationMinutesDraft"
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-[var(--color-ink)] outline-none focus:ring-0" />
                                    <span class="shrink-0 px-3 text-xs font-semibold text-[var(--color-muted)]">minutes</span>
                                </div>
                                <span class="text-[11px] leading-4 text-[var(--color-muted)]">
                                    {{ (int) $villageTradeMaxDurationMinutesDraft }} minutes = {{ $this->formatTradeDurationMinutes((int) $villageTradeMaxDurationMinutesDraft) }}. Travian preview duration must fit this limit before confirmation.
                                </span>
                                @error('villageTradeMaxDurationMinutesDraft')
                                    <span class="text-[11px] font-medium text-red-700">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </section>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--color-line)] px-5 py-3.5">
                <button type="button" wire:click="closeMarketplaceTransferModal"
                    class="rounded-lg border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Cancel
                </button>
                @if ($marketplaceTransferTab === 'send')
                    <button type="button" wire:click="queueManualMarketplaceTransfer"
                        class="rounded-lg bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                        Queue transfer
                    </button>
                @else
                    <button type="button" wire:click="saveMarketplaceTradeSettings"
                        class="rounded-lg bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                        Save trade settings
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
