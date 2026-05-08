package traviaut.b;

import java.util.Arrays;
import java.util.concurrent.TimeUnit;
import traviaut.xml.TAGlobalSets;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/t.class */
public final class t {
    private final long[] a = new long[a.values().length];
    private a b = a.UPDATE1;

    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/t$a.class */
    public enum a {
        DUMMY(true),
        UPDATE1(true),
        UPDATE2(false),
        BUILD1(true),
        BUILD2(false),
        ATTACK(true),
        DEMOLISH(false),
        ACADEMY(false),
        TROOPER1(true),
        TROOPER2(true),
        TROOPER3(true),
        CELEB(true),
        AUTOTRADE(false),
        MERCHANT(false),
        TRADE(true);

        public final boolean o;

        a(boolean z) {
            this.o = z;
        }
    }

    public t() {
        this.a[this.b.ordinal()] = 1;
        this.a[a.UPDATE2.ordinal()] = 1;
    }

    public final a a() {
        return this.b;
    }

    public final long b() {
        return this.a[this.b.ordinal()];
    }

    public final long a(a aVar) {
        return this.a[aVar.ordinal()];
    }

    private long a(long j, a aVar) {
        int iOrdinal = aVar.ordinal();
        return this.a[iOrdinal] < 1 ? j : Math.min(j, this.a[iOrdinal]);
    }

    /* JADX WARN: Multi-variable type inference failed */
    /* JADX WARN: Type inference failed for: r2v2, types: [long, traviaut.b.t$a] */
    public final long c() {
        long jA = a(a(Long.MAX_VALUE, a.TROOPER1), a.TROOPER2);
        ?? r2 = a.TROOPER3;
        long jA2 = a(jA, (a) r2);
        if (r2 == Long.MAX_VALUE) {
            return 0L;
        }
        return jA2;
    }

    public final boolean d() {
        return a(a.TRADE) < System.currentTimeMillis();
    }

    public final boolean a(long j, int i, long j2) {
        return this.a[a.TROOPER1.ordinal() + i] - j < j2;
    }

    public final void a(a aVar, long j, boolean z) {
        long j2 = this.a[aVar.ordinal()];
        if (!z || j >= j2) {
            this.a[aVar.ordinal()] = j;
            f();
        }
    }

    public final void e() {
        Arrays.fill(this.a, 0L);
        this.a[a.UPDATE1.ordinal()] = 1;
        this.a[a.UPDATE2.ordinal()] = 1;
        f();
    }

    /* JADX WARN: Multi-variable type inference failed */
    /* JADX WARN: Type inference failed for: r2v8, types: [long] */
    /* JADX WARN: Type inference failed for: r6v0, types: [long, traviaut.b.t] */
    /* JADX WARN: Type inference failed for: r8v0 */
    /* JADX WARN: Type inference failed for: r8v1 */
    /* JADX WARN: Type inference failed for: r8v2 */
    /* JADX WARN: Type inference failed for: r8v3 */
    /* JADX WARN: Type inference failed for: r8v4 */
    private void f() {
        ?? r8 = 9223372036854775807;
        int i = 0;
        int i2 = 1;
        while (i2 < this.a.length) {
            if (this.a[i2] >= 1 && this < r8) {
                i = i2;
                r8 = this;
            }
            i2++;
            r8 = r8;
        }
        this.b = a.values()[i];
    }

    public final void a(long j, boolean z) {
        int iNextInt;
        long j2 = z ? 1L : 2L;
        TAGlobalSets tAGlobalSetsC = traviaut.f.c();
        int millis = (int) TimeUnit.MINUTES.toMillis(j2 * ((long) tAGlobalSetsC.period));
        if (tAGlobalSetsC.randperiod) {
            int i = millis / 5;
            iNextInt = millis + (traviaut.m.a.nextInt(2 * i) - i);
        } else {
            iNextInt = millis;
        }
        a(z ? a.UPDATE1 : a.UPDATE2, j + ((long) iNextInt), true);
        for (int i2 = 0; i2 < this.a.length; i2++) {
            if (z == a.values()[i2].o && 0 < this.a[i2] && this.a[i2] < j) {
                this.a[i2] = -1;
            }
        }
        f();
    }
}
