package traviaut.d;

import traviaut.b.l;

/* JADX INFO: loaded from: traviaut.jar:traviaut/d/c.class */
public final class c {
    private final b[] a;

    public c() {
        this.a = new b[4];
        for (int i = 0; i < 4; i++) {
            this.a[i] = new b();
        }
    }

    public c(long j, l lVar, l lVar2, l lVar3) {
        this.a = new b[4];
        for (int i = 0; i < 4; i++) {
            this.a[i] = new b(j, lVar.c(i), lVar2.c(i), lVar3.c(i));
        }
    }

    public final b a(int i) {
        return this.a[i];
    }

    public final l a() {
        int[] iArr = new int[4];
        long jCurrentTimeMillis = System.currentTimeMillis();
        for (int i = 0; i < 4; i++) {
            iArr[i] = this.a[i].c(jCurrentTimeMillis);
        }
        return new l(iArr);
    }
}
