package traviaut.a;

import java.util.concurrent.TimeUnit;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/u.class */
public abstract class u implements k {
    private static final long b = TimeUnit.MINUTES.toMillis(10);
    private boolean c = true;
    protected long a;

    public final k.a a(long j, k.a aVar, traviaut.e eVar) {
        if (!a(eVar)) {
            return aVar;
        }
        long jMin = this.c ? Math.min(j, this.a) : this.a;
        return jMin < aVar.d ? new k.a(this, eVar, jMin) : aVar;
    }

    public abstract boolean a(traviaut.e eVar);

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        this.c = false;
        if (a(aVar.c)) {
            this.a = System.currentTimeMillis() + b;
            long jB = b(aVar);
            this.c = jB > 0;
            if (jB > 1) {
                this.a = System.currentTimeMillis() + jB;
            }
        }
    }

    public abstract long b(k.a aVar) throws traviaut.b.i;
}
