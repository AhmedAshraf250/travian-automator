@if ($showProgramSettingsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/55 px-4 py-6 backdrop-blur-sm">
        <div class="w-full max-w-3xl rounded-[2rem] border border-[var(--color-line)] bg-[var(--color-panel)] p-6 shadow-[0_30px_100px_rgba(0,0,0,0.35)]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--color-muted)]">Program settings</p>
                    <h2 class="mt-2 font-[var(--font-display)] text-3xl">Shared defaults for all accounts.</h2>
                    <p class="mt-3 text-sm leading-6 text-[var(--color-muted)]">
                        These defaults apply only when an account does not already define its own dedicated runtime value.
                    </p>
                </div>

                <button type="button" wire:click="closeProgramSettingsModal" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-line-strong)] text-lg font-semibold text-[var(--color-muted)] transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">
                    ×
                </button>
            </div>

            <div class="mt-6 space-y-4">
                <label class="block space-y-3">
                    <span class="text-sm font-semibold">Global fallback user agent</span>
                    <textarea
                        wire:model.live.debounce.500ms="defaultUserAgent"
                        rows="4"
                        class="w-full rounded-[1.5rem] border border-[var(--color-line-strong)] bg-[var(--color-panel-alt)] px-4 py-4 text-sm leading-6 text-[var(--color-ink)] outline-none transition placeholder:text-[var(--color-muted)] focus:border-[var(--color-accent)] focus:ring-4 focus:ring-[color:var(--color-accent-soft)]"
                        placeholder="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ..."
                    ></textarea>
                </label>

                @error('defaultUserAgent')
                    <p class="rounded-2xl border border-rose-700/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
                @enderror

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4 text-sm text-[var(--color-muted)]">
                        If an account has its own user agent, that account-specific value still wins. The global fallback only fills the gap for accounts left blank.
                    </div>

                    <div class="rounded-[1.5rem] bg-[var(--color-panel-alt)] px-4 py-4 text-sm text-[var(--color-muted)]">
                        This panel is intentionally small for now. More shared runtime settings can be added here later without mixing them into account-specific controls.
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <button type="button" wire:click="closeProgramSettingsModal" class="rounded-full border border-[var(--color-line-strong)] px-4 py-2 text-sm font-semibold transition hover:border-[var(--color-accent)] hover:text-[var(--color-accent)]">Cancel</button>
                <button type="button" wire:click="saveProgramSettings" class="rounded-full bg-[var(--color-accent)] px-5 py-2.5 text-sm font-semibold text-[var(--color-accent-contrast)] shadow-[0_16px_40px_rgba(176,93,31,0.35)] transition hover:brightness-110">Save settings</button>
            </div>
        </div>
    </div>
@endif
