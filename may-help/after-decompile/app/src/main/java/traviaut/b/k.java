package traviaut.b;

import java.util.Arrays;
import java.util.Iterator;
import traviaut.b.s;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/k.class */
public final class k {
    public final traviaut.d.c a;
    private s.a d;
    private l c = new l();
    public final l b = new l();
    private final int[] e = new int[11];

    public final s.a a() {
        return this.d;
    }

    public final String b() {
        return s.a(this.e);
    }

    public k(Iterable<r> iterable) {
        l lVar = new l();
        this.d = s.a;
        Arrays.fill(this.e, 0);
        Iterator<r> it = iterable.iterator();
        while (it.hasNext()) {
            s sVarA = it.next().a();
            lVar.a(sVarA.c());
            this.c.a(sVarA.d);
            this.b.a(sVarA.e);
            s.a aVar = sVarA.b;
            if (aVar.a.b() < this.d.a.b()) {
                this.d = aVar;
            }
            for (int i = 0; i < 11; i++) {
                int[] iArr = this.e;
                int i2 = i;
                iArr[i2] = iArr[i2] + sVarA.j[i];
            }
        }
        this.a = new traviaut.d.c(System.currentTimeMillis(), this.b, lVar, this.c);
    }
}
