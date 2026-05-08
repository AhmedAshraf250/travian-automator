package traviaut.a;

import org.w3c.dom.Element;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/m.class */
public final class m extends u {
    private static final String[] b = {"power", "offBonus", "defBonus", "productionPoints", "regenBonus"};

    @Override // traviaut.a.u
    public final boolean a(traviaut.e eVar) {
        return l.b(eVar) && eVar.c().expsEnabled() && eVar.e.e;
    }

    @Override // traviaut.a.u
    public final long b(k.a aVar) throws traviaut.b.i {
        traviaut.e eVar = aVar.c;
        eVar.a("upgrading hero experience");
        traviaut.b.h hVarB = eVar.b(eVar.e.c ? "hero_inventory.php" : "hero.php");
        if (!hVarB.b()) {
            eVar.a("failed to load inventory");
            return 0L;
        }
        Element elementA = hVarB.a("table", "id", "attributesOfHero");
        int iC = traviaut.m.c(traviaut.b.h.a(traviaut.b.h.a(elementA, "span", "id", "availablePoints")));
        boolean[] zArr = new boolean[5];
        int[] iArr = new int[5];
        int[] iArr2 = new int[5];
        for (int i = 0; i < 5; i++) {
            Element elementA2 = traviaut.b.h.a(elementA, "input", "name", "attribute" + b[i]);
            zArr[i] = elementA2 != null;
            if (elementA2 != null) {
                iArr[i] = traviaut.m.c(elementA2.getAttribute("value"));
            }
        }
        int[] exps = eVar.c().getExps();
        for (int i2 = 0; i2 < iC; i2++) {
            int iA = a(zArr, iArr, exps);
            if (iA == -1) {
                eVar.a("not able to upgrade hero experience - check your settings");
                return 0L;
            }
            iArr[iA] = iArr[iA] + 1;
            iArr2[iA] = iArr2[iA] + 1;
        }
        traviaut.j jVar = new traviaut.j(hVarB.a("div", "class", "heroPropertiesContent"));
        jVar.a(zArr, b, iArr2);
        jVar.a("heroSetAttributes");
        eVar.c.b(jVar);
        eVar.e.e = false;
        eVar.a("upgraded " + traviaut.m.a(iArr2));
        return 1L;
    }

    private static int a(boolean[] zArr, int[] iArr, int[] iArr2) {
        double d = Double.MAX_VALUE;
        int i = -1;
        for (int i2 = 0; i2 < iArr2.length; i2++) {
            if (zArr[i2] && iArr2[i2] != 0 && iArr[i2] != 100) {
                double d2 = ((double) iArr[i2]) / ((double) iArr2[i2]);
                if (d2 < d) {
                    d = d2;
                    i = i2;
                }
            }
        }
        return i;
    }
}
