package traviaut.a;

import java.util.Iterator;
import java.util.concurrent.TimeUnit;
import traviaut.b.m;
import traviaut.b.t;
import traviaut.k;
import traviaut.xml.TATrader;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/d.class */
public final class d extends b {
    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        return rVar.e.trader.autotrade > 0;
    }

    @Override // traviaut.a.b
    protected final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
    }

    @Override // traviaut.a.b
    protected final boolean b(traviaut.b.r rVar) {
        return rVar.f.a(t.a.AUTOTRADE) <= System.currentTimeMillis();
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        TATrader tATrader = aVar.b.e.trader;
        aVar.b.f.a(t.a.AUTOTRADE, System.currentTimeMillis() + TimeUnit.MINUTES.toMillis(tATrader.period), true);
        Iterator<traviaut.b.r> it = aVar.c.e.a.a.iterator();
        while (true) {
            if (!it.hasNext()) {
                break;
            }
            traviaut.b.r next = it.next();
            if (next.a == aVar.b.e.trader.autotrade) {
                aVar.b.a("autotrade to: " + next.a().a());
                traviaut.k.a(new traviaut.b.n(next, new traviaut.b.m(m.b.EQ_REMAINING).b(tATrader.automaxmerch).a()).a(aVar.b).a(tATrader.autoreservemerch).d(), next);
                break;
            }
        }
        aVar.b.f();
    }
}
