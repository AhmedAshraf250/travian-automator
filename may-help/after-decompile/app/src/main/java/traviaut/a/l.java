package traviaut.a;

import org.w3c.dom.Element;
import traviaut.Main;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/l.class */
public final class l extends u {
    @Override // traviaut.a.u
    public final boolean a(traviaut.e eVar) {
        if (!b(eVar) || !eVar.c().adventures) {
            return false;
        }
        traviaut.a aVar = eVar.e;
        return aVar.f && aVar.d;
    }

    @Override // traviaut.a.u
    public final long b(k.a aVar) throws traviaut.b.i {
        traviaut.e eVar = aVar.c;
        traviaut.b.h hVarB = eVar.b(eVar.e.c ? "hero_inventory.php" : "hero.php");
        if (!hVarB.b()) {
            return 0L;
        }
        eVar.a("trying hero adventure");
        Element elementA = hVarB.a("div", "class", "powervalue");
        Element elementA2 = elementA;
        if (elementA == null) {
            elementA2 = hVarB.a("td", "class", "powervalue");
        }
        if (elementA2 == null) {
            eVar.a("failed to read hero health");
            return 0L;
        }
        int iC = traviaut.m.c(traviaut.b.h.a(elementA2));
        if (iC < eVar.c().herohealth) {
            eVar.a("hero - not enough health: " + iC);
            return 0L;
        }
        Element elementA3 = eVar.b(eVar.e.c ? "hero_adventure.php" : "hero.php?t=3").a("form", "id", "adventureListForm");
        if (elementA3 == null) {
            eVar.a("no adventure form");
            return 0L;
        }
        String strA = org.a.f.a("hero", elementA3);
        if (strA.isEmpty()) {
            return 0L;
        }
        String[] strArrSplit = strA.split(" ");
        String str = strArrSplit[0];
        String str2 = strArrSplit[1];
        Element elementA4 = eVar.b(str).a("form", "class", "adventureSendButton");
        if (elementA4 == null) {
            eVar.a("hero not available");
            return 0L;
        }
        traviaut.j jVar = new traviaut.j(elementA4);
        eVar.a("hero adventure, time: " + str2);
        eVar.a(jVar);
        return traviaut.m.b(str2) << 1;
    }

    public static boolean b(traviaut.e eVar) {
        traviaut.n nVarA;
        if (Main.a() && (nVarA = eVar.e.a()) != null) {
            return traviaut.a.b(nVarA);
        }
        return false;
    }
}
