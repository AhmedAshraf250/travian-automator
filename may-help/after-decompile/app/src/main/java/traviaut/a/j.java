package traviaut.a;

import org.w3c.dom.Element;
import traviaut.Main;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/j.class */
public final class j extends u {
    private static int b(traviaut.e eVar) {
        return eVar.c().getGoldEx(eVar.e.i);
    }

    @Override // traviaut.a.u
    public final boolean a(traviaut.e eVar) {
        return Main.b() && b(eVar) > 0;
    }

    @Override // traviaut.a.u
    public final long b(k.a aVar) throws traviaut.b.i {
        traviaut.e eVar = aVar.c;
        int iB = b(eVar);
        eVar.a("getting gold: " + iB);
        traviaut.b.h hVarB = eVar.b(eVar.e.c ? "hero_auction.php" : "hero.php?t=4");
        if (hVarB.a("div", "class", "auctionAdventureBarNumbers") != null) {
            eVar.a("not enough adventures");
            return 0L;
        }
        Element elementA = hVarB.a("div", "id", "silverExchange");
        if (elementA == null) {
            eVar.a("no exchange form");
            return 0L;
        }
        traviaut.j jVar = new traviaut.j(traviaut.b.h.a(elementA, "form", 0));
        jVar.a("silverExchange");
        jVar.b(iB);
        org.a.c cVarB = eVar.c.b(jVar).b("response");
        if ("true".equals(cVarB.c("error"))) {
            eVar.a("failed: " + cVarB.c("errorMsg"));
            return 0L;
        }
        org.a.c cVarB2 = cVarB.b("data");
        if (!"true".equals(cVarB2.c("result"))) {
            eVar.a("failed to get result");
            return 0L;
        }
        eVar.e.h = cVarB2.d("newGold");
        eVar.e.i = cVarB2.d("newSilver");
        eVar.a("gold exchange succeeded");
        return 1L;
    }
}
