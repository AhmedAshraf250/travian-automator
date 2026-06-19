@if ($showImportModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-[var(--color-line)] px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[var(--color-line)] bg-[var(--color-panel-alt)] text-lg font-black text-[var(--color-accent)]">
                        ⇥
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Bulk accounts and login check</p>
                        <h2 class="mt-1 font-[var(--font-display)] text-2xl font-semibold">Accounts &amp; Login</h2>
                    </div>
                </div>

                <button type="button" wire:click="closeImportModal"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <section class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel)] p-3 shadow-sm">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-[var(--color-ink)]">Input account lines</h3>
                        <div class="flex flex-wrap gap-2 text-xs text-[var(--color-muted)]">
                            <code class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1">
                                !server!username!password!proxy_url!user_agent
                            </code>
                            <code class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1">
                                server username password proxy_url user_agent
                            </code>
                        </div>
                    </div>

                    <textarea wire:model.live.debounce.350ms="bulkImportDraft" rows="8"
                        class="w-full rounded-lg border border-[var(--color-line-strong)] bg-white px-3 py-3 font-mono text-sm leading-6 text-[var(--color-ink)] outline-none transition placeholder:text-[var(--color-muted)] focus:border-[var(--color-accent)] focus:ring-4 focus:ring-[color:var(--color-accent-soft)]"
                        placeholder="!https://ts7.x1.arabics.travian.com/!marshal!12345678&#10;https://ts7.x1.arabics.travian.com/ marshal 12345678"></textarea>
                </section>

                @error('bulkImportDraft')
                    <p class="rounded-lg border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                @enderror

                <section class="rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] p-3">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-[var(--color-ink)]">Preview generated from input</h3>
                        <span class="rounded-md bg-[var(--color-panel)] px-2 py-1 text-[11px] font-semibold text-[var(--color-muted)]">
                            {{ count($importPreviewRows) }} line(s)
                        </span>
                    </div>

                    <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                        @forelse ($importPreviewRows as $previewRow)
                            <article wire:key="import-preview-row-{{ $previewRow['line'] }}"
                                class="rounded-lg border bg-[var(--color-panel)] px-3 py-2 {{ $previewRow['valid'] ? 'border-emerald-500/25' : 'border-rose-500/35' }}">
                                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                                    <span class="rounded-md px-2 py-1 font-bold {{ $previewRow['valid'] ? 'bg-emerald-500/10 text-emerald-800' : 'bg-rose-500/10 text-rose-800' }}">
                                        #{{ $previewRow['line'] }}
                                    </span>

                                    @if ($previewRow['valid'])
                                        <span class="max-w-full truncate rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1 font-semibold text-[var(--color-ink)]">
                                            server: {{ $previewRow['server'] }}
                                        </span>
                                        <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1 font-semibold text-[var(--color-ink)]">
                                            user: {{ $previewRow['username'] }}
                                        </span>
                                        <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1 font-semibold text-[var(--color-muted)]">
                                            password: {{ $previewRow['password'] }}
                                        </span>
                                        <span class="rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1 font-semibold text-[var(--color-muted)]">
                                            proxy: {{ $previewRow['proxy'] }}
                                        </span>
                                        <span class="max-w-full truncate rounded-md border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-2 py-1 font-semibold text-[var(--color-muted)]">
                                            UA: {{ $previewRow['user_agent'] }}
                                        </span>
                                    @else
                                        <span class="min-w-0 flex-1 truncate font-semibold text-rose-800">
                                            {{ $previewRow['error'] }}
                                        </span>
                                        <span class="max-w-full truncate rounded-md bg-rose-500/10 px-2 py-1 font-mono text-rose-900">
                                            {{ $previewRow['server'] }}
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="rounded-lg border border-dashed border-[var(--color-line-strong)] bg-[var(--color-panel)] px-4 py-5 text-sm text-[var(--color-muted)]">
                                No account lines yet.
                            </p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--color-line)] px-5 py-4">
                <button type="button" wire:click="closeImportModal"
                    class="rounded-lg border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Cancel
                </button>
                <button type="button" wire:click="importAccounts"
                    class="rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-5 py-2.5 text-sm font-semibold text-[var(--color-ink)] shadow-sm transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Save &amp; queue login
                </button>
            </div>
        </div>
    </div>
@endif
