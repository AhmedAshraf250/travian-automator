package traviaut;

import java.util.concurrent.TimeUnit;

/* JADX INFO: loaded from: traviaut.jar:traviaut/d.class */
public final class d {
    private long a;
    private long b;
    private String c;

    public final boolean a(long j) {
        return j < a();
    }

    public final long a() {
        return this.a + this.b;
    }

    public final String b() {
        return this.c;
    }

    public final void a(int i, String str) {
        long jCurrentTimeMillis = System.currentTimeMillis();
        long millis = TimeUnit.MINUTES.toMillis(i);
        long jMin = millis;
        if (millis == 0) {
            jMin = Math.min(Math.max((3 * this.b) / 2, TimeUnit.SECONDS.toMillis(2L)), TimeUnit.MINUTES.toMillis(1L));
        }
        if (jCurrentTimeMillis + jMin <= a()) {
            return;
        }
        this.a = jCurrentTimeMillis;
        this.b = jMin;
        this.c = str;
    }

    public final void a(long j, String str) {
        this.a = System.currentTimeMillis();
        this.b = j;
        this.c = str;
    }

    public final void c() {
        this.b = 0L;
        this.a = 0L;
        this.c = "";
    }
}
