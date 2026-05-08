package traviaut.a;

import java.util.List;
import java.util.concurrent.TimeUnit;
import traviaut.Main;
import traviaut.b.m;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/r.class */
public final class r extends b {
    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        return Main.b() && traviaut.f.c().checkoverflow && System.currentTimeMillis() >= rVar.l && rVar.a().l.e(17) != 0;
    }

    @Override // traviaut.a.b
    protected final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
    }

    private static int d(traviaut.b.r rVar) {
        traviaut.b.s sVarA = rVar.a();
        traviaut.b.l lVarF = sVarA.e.f();
        traviaut.b.l lVarD = sVarA.d();
        for (int i = 0; i < 4; i++) {
            if (lVarF.c(i) > lVarD.c(i)) {
                return i;
            }
        }
        return -1;
    }

    private static List<traviaut.b.r> a(traviaut.b.r rVar, int i) {
        return new traviaut.b.q(rVar, i).a();
    }

    @Override // traviaut.a.b
    protected final boolean b(traviaut.b.r rVar) {
        int iD;
        return rVar.d.e.a.a.size() >= 2 && rVar.e() && (iD = d(rVar)) != -1 && !a(rVar, iD).isEmpty();
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        traviaut.b.r rVar = aVar.b;
        rVar.l = System.currentTimeMillis() + TimeUnit.MINUTES.toMillis(10L);
        int iD = d(rVar);
        if (iD == -1) {
            return;
        }
        traviaut.b.s sVarA = rVar.a();
        int iE = traviaut.b.l.e(Math.min(sVarA.e.c(iD) << 2, sVarA.c().c(iD)));
        for (traviaut.b.r rVar2 : a(rVar, iD)) {
            int iE2 = traviaut.b.l.e(Math.min(rVar2.a().d().c(iD) - (rVar2.a().e.c(iD) << 2), iE));
            if (iE2 > 0) {
                int[] iArr = new int[4];
                iArr[iD] = iE2;
                traviaut.k.a(new traviaut.b.n(rVar2, new traviaut.b.m(m.b.EQ_TRADE).a(new traviaut.b.l(iArr))).a().a(rVar).e(), rVar);
                int i = iE - iE2;
                iE = i;
                if (i == 0) {
                    break;
                }
            }
        }
        traviaut.k.a(rVar);
    }
}
