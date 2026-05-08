package traviaut.a;

import java.util.concurrent.TimeUnit;
import org.w3c.dom.Element;
import traviaut.b.t;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/h.class */
public final class h extends b {
    private static final traviaut.b.l a = new traviaut.b.l(new int[]{6400, 6650, 5940, 1340});
    private static final traviaut.b.l b = new traviaut.b.l(new int[]{29700, 33250, 32000, 6700});

    private static int d(traviaut.b.r rVar) {
        return rVar.a().l.e(24);
    }

    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        if (rVar.e.celebrations) {
            return traviaut.f.c().celeb.isAllowed(d(rVar));
        }
        return false;
    }

    private boolean e(traviaut.b.r rVar) {
        return (traviaut.f.c().celeb.getLeastPossible(d(rVar)) ? b : a).e(rVar.a().c());
    }

    @Override // traviaut.a.b
    public final boolean b(traviaut.b.r rVar) {
        long jA = rVar.f.a(t.a.CELEB);
        if (jA == 0) {
            return true;
        }
        if (jA > System.currentTimeMillis()) {
            return false;
        }
        return e(rVar);
    }

    private static void a(long j, traviaut.b.r rVar) {
        if (j == 0) {
            j = TimeUnit.MINUTES.toMillis(10L);
        }
        if (j > 0) {
            j += System.currentTimeMillis();
        }
        rVar.f.a(t.a.CELEB, j, false);
        rVar.d();
    }

    @Override // traviaut.a.b
    public final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        long jA = rVar.f.a(t.a.CELEB);
        if (jA != 0 && jA <= j + traviaut.b.r.g()) {
            lVar.a(traviaut.f.c().celeb.isGreatWanted(d(rVar)) ? b : a);
        }
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        aVar.b.a(false);
        traviaut.b.h hVarB = aVar.b.d.b(traviaut.b.b.b(24));
        long jD = hVarB.d();
        if (jD >= 0 || !e(aVar.b)) {
            a(jD, aVar.b);
            aVar.b.f();
            return;
        }
        traviaut.b.r rVar = aVar.b;
        Element elementA = hVarB.a("div", "class", "researches");
        if (elementA == null) {
            rVar.a("celebrations: no research link");
            a(0L, rVar);
            return;
        }
        int iE = rVar.a().l.e(24);
        traviaut.b.l lVarC = rVar.a().c();
        boolean zIsGreatWanted = traviaut.f.c().celeb.isGreatWanted(iE);
        if (traviaut.f.c().celeb.isAny() && !b.e(lVarC)) {
            zIsGreatWanted = false;
        }
        String strA = org.a.f.a("celeb " + (zIsGreatWanted ? "a=2" : "a=1"), elementA);
        if (!strA.isEmpty()) {
            rVar.a("running celebration");
            traviaut.b.h hVarB2 = rVar.d.b(strA);
            if (hVarB2.d() > 0) {
                rVar.a("celebrations: " + traviaut.m.b((long) "researches"));
                a("researches", rVar);
                rVar.a(new traviaut.b.j(hVarB2));
                return;
            }
        }
        rVar.a("celebrations: unknown error");
        a(0L, rVar);
    }
}
