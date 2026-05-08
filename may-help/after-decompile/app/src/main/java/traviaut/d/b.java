package traviaut.d;

import java.util.concurrent.TimeUnit;

/* JADX INFO: loaded from: traviaut.jar:traviaut/d/b.class */
public final class b extends a {
    private static final int a = (int) TimeUnit.HOURS.toMillis(1);
    private static final int b = (int) TimeUnit.SECONDS.toMillis(1);
    private final long c;
    private final int d;
    private final int e;
    private final int f;
    private final int g;

    public b() {
        this.f = 0;
        this.e = 0;
        this.d = 0;
        this.c = 0L;
        this.g = b;
    }

    public b(long j, int i, int i2, int i3) {
        this.c = j;
        this.d = i;
        this.e = i2;
        this.f = i3;
        this.g = Math.max(this.d != 0 ? a / this.d : b, 100);
    }

    public final int c(long j) {
        return Math.min(this.e + ((int) (((j - this.c) * ((long) this.d)) / ((long) a))), this.f);
    }

    @Override // traviaut.d.a
    protected final String a(long j) {
        return Integer.toString(c(j));
    }

    @Override // traviaut.d.a
    public final int a() {
        return this.g;
    }
}
