package traviaut.a;

import org.w3c.dom.Element;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/v.class */
public final class v extends b {
    private static final traviaut.b.l a = new traviaut.b.l(new int[]{6000, 6000, 7500, 5500});

    private static int a(traviaut.b.b bVar) {
        if (bVar.e(25) > 0) {
            return 25;
        }
        return bVar.e(26) > 0 ? 26 : 0;
    }

    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        if (!rVar.e.troops.settlers) {
            return false;
        }
        traviaut.b.b bVar = rVar.a().l;
        return bVar.e(a(bVar)) >= rVar.m;
    }

    @Override // traviaut.a.b
    public final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        lVar.a(d(rVar));
    }

    @Override // traviaut.a.b
    public final boolean b(traviaut.b.r rVar) {
        return d(rVar).e(rVar.a().c());
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        traviaut.b.r rVar = aVar.b;
        rVar.a(true);
        String str = traviaut.b.b.b(a(rVar.a().l)) + "&s=1";
        Element elementA = rVar.d.b(str).a("form", "name", "snd");
        int iA = y.a(elementA, 10);
        if (iA == -1) {
            if (rVar.m == 20) {
                rVar.a("no more settlers possible, switching off");
                rVar.e.troops.settlers = false;
                return;
            } else {
                rVar.a("no settlers possible yet");
                rVar.m = Math.min(20, rVar.m + 5);
                rVar.f();
                return;
            }
        }
        if (iA == 0) {
            return;
        }
        traviaut.j jVar = new traviaut.j(elementA);
        jVar.b(10, iA);
        rVar.a("settlers cnt: " + iA);
        rVar.d.a(jVar);
        traviaut.b.h hVarB = rVar.d.b(str);
        if (hVarB.a("form", "name", "snd") == null) {
            rVar.a("no more settlers possible, switching off");
            rVar.e.troops.settlers = false;
        }
        rVar.a(new traviaut.b.j(hVarB));
    }

    private static traviaut.b.l d(traviaut.b.r rVar) {
        return !traviaut.a.b(rVar.d.e.a()) ? a : rVar.d.d()[9].a;
    }
}
