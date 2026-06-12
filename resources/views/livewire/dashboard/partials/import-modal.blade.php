@if ($showImportModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="w-full max-w-3xl overflow-hidden rounded-lg border border-[var(--color-line)] bg-[var(--color-panel)] shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-[var(--color-line)] px-5 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase text-[var(--color-muted)]">Bulk import</p>
                    <h2 class="mt-1 font-[var(--font-display)] text-2xl font-semibold">Import accounts</h2>
                </div>

                <button type="button" wire:click="closeImportModal"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="space-y-4 px-5 py-4">
                <code class="block rounded-lg border border-[var(--color-line)] bg-[var(--color-panel-alt)] px-3 py-2 text-xs text-[var(--color-muted)]">
                    !server!username!password!proxy_ip!proxy_port!user_agent
                </code>

                <label class="block space-y-2">
                    <span class="text-sm font-semibold">Textarea draft</span>
                    <textarea wire:model.live.debounce.500ms="bulkImportDraft" rows="12"
                        class="w-full rounded-lg border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-3 py-3 text-sm leading-6 text-[var(--color-ink)] outline-none transition placeholder:text-[var(--color-muted)] focus:border-[var(--color-accent)] focus:ring-4 focus:ring-[color:var(--color-accent-soft)]"
                        placeholder="!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0 ..."></textarea>
                </label>

                @error('bulkImportDraft')
                    <p class="rounded-lg border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--color-line)] px-5 py-4">
                <button type="button" wire:click="closeImportModal"
                    class="rounded-lg border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    Cancel
                </button>
                <button type="button" wire:click="importAccounts"
                    class="rounded-lg bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-sm transition hover:brightness-105">
                    Parse &amp; import
                </button>
            </div>
        </div>
    </div>
@endif
