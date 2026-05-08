package traviaut.b;

import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.List;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/u.class */
public final class u implements Comparator<r> {
    private final int b;
    private final int c;
    public final List<r> a = new ArrayList();

    public u(int i, int i2) {
        this.b = i;
        this.c = i2;
    }

    public u(r rVar) {
        this.b = rVar.b;
        this.c = rVar.c;
    }

    public final void a(r rVar) {
        this.a.add(rVar);
    }

    public final void a() {
        Collections.sort(this.a, this);
    }

    @Override // java.util.Comparator
    public final /* synthetic */ int compare(r rVar, r rVar2) {
        r rVar3 = rVar;
        r rVar4 = rVar2;
        double dA = rVar3.a(this.b, this.c) - rVar4.a(this.b, this.c);
        if (Math.abs(dA) >= 1.0E-5d) {
            return dA < 0.0d ? -1 : 1;
        }
        int i = rVar3.a;
        int i2 = rVar4.a;
        if (i == i2) {
            return 0;
        }
        return i < i2 ? -1 : 1;
    }
}
