package traviaut.a;

import java.util.concurrent.TimeUnit;
import traviaut.Main;
import traviaut.b.t;
import traviaut.k;
import traviaut.xml.TAMerchant;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/p.class */
public final class p extends b {
    private static final String[] a = {"lumber", "clay", "iron", "crop"};

    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        if (!Main.b()) {
            return false;
        }
        TAMerchant tAMerchant = traviaut.f.c().merchant;
        if (!tAMerchant.enabled) {
            return false;
        }
        traviaut.b.s sVarA = rVar.a();
        int iE = sVarA.l.e(17);
        return iE >= tAMerchant.marketlimit && iE >= tAMerchant.merchants + tAMerchant.merchantreserve && sVarA.l.e(28) >= tAMerchant.officelimit;
    }

    @Override // traviaut.a.b
    protected final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
    }

    private static boolean a(t tVar) {
        return ((int) ((((long) tVar.c[tVar.b]) * 100) / ((long) tVar.c[tVar.a]))) < traviaut.f.c().merchant.resbalancefactor;
    }

    @Override // traviaut.a.b
    protected final boolean b(traviaut.b.r rVar) {
        if (rVar.f.a(t.a.MERCHANT) > System.currentTimeMillis() || !rVar.e()) {
            return false;
        }
        TAMerchant tAMerchant = traviaut.f.c().merchant;
        t tVar = new t(rVar.d.e.b.a().a.a(), tAMerchant);
        if (a(tVar)) {
            return false;
        }
        if (tVar.b != 3 || (tAMerchant.sellcrop && !rVar.a().e.a())) {
            return rVar.a().c().c(tVar.b) >= tAMerchant.merchants * rVar.h();
        }
        return false;
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        TAMerchant tAMerchant = traviaut.f.c().merchant;
        traviaut.b.r rVar = aVar.b;
        rVar.f.a(t.a.MERCHANT, System.currentTimeMillis() + TimeUnit.MINUTES.toMillis(tAMerchant.period), false);
        t tVar = new t(rVar.d.e.b.a().a.a(), tAMerchant);
        if (a(tVar)) {
            return;
        }
        rVar.a(false);
        traviaut.n nVarA = rVar.d.e.a();
        String strB = traviaut.b.b.b(17);
        if (nVarA.compareTo(traviaut.n.VER40a) >= 0) {
            strB = strB + "&t=2";
        }
        traviaut.b.h hVarB = rVar.d.b(strB);
        if (traviaut.m.c(traviaut.b.h.a(hVarB.a("span", "class", "merchantsAvailable"))) - tAMerchant.merchantreserve < tAMerchant.merchants) {
            rVar.a("not enough merchants for market offer");
            return;
        }
        int iH = tAMerchant.merchants * rVar.h();
        if (rVar.a().c().c(tVar.b) < iH) {
            rVar.a("not enough resources for market offer");
            return;
        }
        traviaut.j jVar = new traviaut.j(hVarB.a("form", "class", "sell_resources"));
        jVar.a(tVar.a, tVar.b, iH);
        rVar.a("offering: " + a[tVar.b] + " -> " + a[tVar.a] + " amount: " + iH);
        rVar.a(new traviaut.b.j(rVar.d.a(jVar)));
    }
}
