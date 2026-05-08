package traviaut.a;

import org.w3c.dom.Element;
import traviaut.k;
import traviaut.xml.TAAcc;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/q.class */
public final class q extends b {
    private static final traviaut.b.l a = new traviaut.b.l(750);

    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        return rVar.d.b.isNewVill() && rVar.d.e.m != 0 && rVar.a().j[10] >= 3;
    }

    @Override // traviaut.a.b
    protected final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        lVar.a(a);
    }

    @Override // traviaut.a.b
    protected final boolean b(traviaut.b.r rVar) {
        if (System.currentTimeMillis() < rVar.d.e.n) {
            return false;
        }
        return a.e(rVar.a().c());
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        aVar.b.a(false);
        traviaut.e eVar = aVar.b.d;
        TAAcc tAAcc = eVar.b;
        aVar.b.a("settling new village at [" + tAAcc.nx + ":" + tAAcc.ny + "]");
        Element elementA = eVar.b("build.php?gid=16&tt=2&mapid=" + traviaut.m.a(tAAcc.nx, tAAcc.ny) + "&s=1").a("div", "id", "content");
        String strC = new traviaut.b.e(elementA).a("table", "class", "troop_details").a("tbody", "class", "infos").a("td", "colspan", "11").c();
        if (strC == null) {
            aVar.b.a("invalid position for a new village, resetting coords");
            tAAcc.resetVill();
            return;
        }
        long jB = traviaut.m.b(strC);
        eVar.a(new traviaut.j(traviaut.b.h.a(elementA, "form", 0)));
        eVar.e.n = System.currentTimeMillis() + jB;
        tAAcc.resetVill();
        traviaut.k.a(aVar.b);
        aVar.b.a("settling done");
    }
}
