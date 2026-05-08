package traviaut.a;

import org.w3c.dom.Element;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/n.class */
public final class n extends u {
    @Override // traviaut.a.u
    public final boolean a(traviaut.e eVar) {
        if (l.b(eVar) && traviaut.f.c().hero.revive) {
            return eVar.e.g;
        }
        return false;
    }

    @Override // traviaut.a.u
    public final long b(k.a aVar) throws traviaut.b.i {
        traviaut.e eVar = aVar.c;
        traviaut.b.h hVarB = eVar.b(eVar.e.c ? "hero_inventory.php" : "hero.php");
        if (!hVarB.b()) {
            return 0L;
        }
        eVar.a("reviving dead hero");
        Element elementA = hVarB.a("div", "class", "hero-dead");
        if (elementA == null) {
            eVar.a("unknown hero status");
            return 0L;
        }
        Element elementA2 = traviaut.b.h.a(elementA, "form", "method", "post");
        if (elementA2 == null) {
            eVar.a("no form for ressurect");
            return 0L;
        }
        traviaut.j jVar = new traviaut.j(elementA2);
        if (jVar.b() < 2) {
            eVar.a("not enough resources");
            return 0L;
        }
        String strA = traviaut.b.h.a(traviaut.b.h.a(elementA, "span", "class", "clocks"));
        eVar.a("hero ressurect time: " + strA);
        eVar.a(jVar);
        return traviaut.m.b(strA);
    }
}
