package traviaut.b;

import java.util.Comparator;
import java.util.List;
import java.util.stream.Collectors;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/q.class */
public final class q implements Comparator<r> {
    private final r a;
    private final int b;
    private final int c;

    public q(r rVar, int i) {
        this.a = rVar;
        this.b = i;
        this.c = rVar.a().e.c(i);
    }

    public final List<r> a() {
        return (List) this.a.d.e.a.a.stream().filter(rVar -> {
            return rVar.a != this.a.a;
        }).filter(rVar2 -> {
            return 2 * this.c < rVar2.a().d().c(this.b);
        }).filter(rVar3 -> {
            return rVar3.a(this.a) < ((double) traviaut.f.c().tradedistlimit);
        }).sorted(this).collect(Collectors.toList());
    }

    @Override // java.util.Comparator
    public final /* synthetic */ int compare(r rVar, r rVar2) {
        r rVar3 = rVar;
        r rVar4 = rVar2;
        double dA = rVar3.a(this.a);
        return Double.compare(((double) rVar4.a().d().c(this.b)) - (rVar4.a(this.a) * 5000.0d), rVar3.a().d().c(this.b) - (dA * 5000.0d));
    }
}
