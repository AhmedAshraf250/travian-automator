package traviaut.d;

import java.util.concurrent.TimeUnit;
import traviaut.m;

/* JADX INFO: loaded from: traviaut.jar:traviaut/d/d.class */
public final class d extends a {
    private final long a;

    public d(long j) {
        this.a = j;
    }

    @Override // traviaut.d.a
    protected final String a(long j) {
        return this.a == 0 ? "" : this.a < j ? "?" : m.b(this.a - j);
    }

    @Override // traviaut.d.a
    public final int a() {
        return (int) TimeUnit.SECONDS.toMillis(1L);
    }

    public final long b() {
        return this.a;
    }
}
