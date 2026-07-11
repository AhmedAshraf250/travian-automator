<div x-show="dashboardManualLoading" x-cloak x-transition.opacity.duration.100ms
    class="fixed inset-0 z-[70] bg-slate-950/[0.03] backdrop-blur-[1.5px]" role="status" aria-live="polite"
    aria-label="Working">
    <div class="pointer-events-none fixed flex h-12 w-12 items-center justify-center rounded-full border-2 border-slate-950/70 bg-white/50 shadow-[0_10px_26px_rgba(15,23,42,0.22)] ring-2 ring-white/35"
        :style="`left: ${dashboardLoadingPoint.x}px; top: ${dashboardLoadingPoint.y}px; transform: translate(-50%, -50%);`">
        <div class="relative h-8 w-8 animate-spin">
            @foreach (range(0, 11) as $spinnerIndex)
                <span class="absolute left-1/2 top-1/2 h-3.5 w-1.5 rounded-full bg-[var(--color-accent)]"
                    style="transform: translate(-50%, -50%) rotate({{ $spinnerIndex * 30 }}deg) translateY(-0.95rem); opacity: {{ number_format(1 - $spinnerIndex * 0.055, 2) }};"></span>
            @endforeach
        </div>
    </div>
</div>
