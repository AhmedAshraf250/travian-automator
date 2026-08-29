document.addEventListener('alpine:init', () => {
    Alpine.store('dashboardClock', {
        now: Date.now(),
        intervalId: null,

        init() {
            this.start();

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.now = Date.now();
                    this.start();
                }
            });
        },

        start() {
            if (this.intervalId !== null) {
                clearInterval(this.intervalId);
            }

            this.now = Date.now();
            this.intervalId = setInterval(() => {
                this.now = Date.now();
            }, 1000);
        },
    });
});
