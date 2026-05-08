package traviaut.a;

import java.util.concurrent.TimeUnit;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import traviaut.b.t;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/a.class */
public final class a extends b {
    private static traviaut.b.l a(Element element) {
        int[] iArr = new int[4];
        for (int i = 0; i < 4; i++) {
            iArr[i] = traviaut.m.c(new traviaut.b.e(element).a("div", "class", "costs").a("span", "class", "r" + (i + 1)).c());
        }
        return new traviaut.b.l(iArr);
    }

    private static int a(traviaut.b.h hVar) {
        String[] strArrSplit = new traviaut.b.e(hVar.a("div", "id", "content")).a("table", "class", "under_progress").a("tbody").a("tr").a("img").c("class").split(" ");
        if (strArrSplit.length < 2) {
            return -1;
        }
        int iC = traviaut.m.c(strArrSplit[1].substring(1));
        while (iC > 10) {
            iC -= 10;
        }
        return iC - 1;
    }

    private static long d(traviaut.b.r rVar) {
        return rVar.f.a(t.a.ACADEMY);
    }

    private static void a(traviaut.b.r rVar, long j) {
        if (j > 1) {
            j += System.currentTimeMillis();
        }
        rVar.f.a(t.a.ACADEMY, j, false);
    }

    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        if (rVar.a().l.e(22) == 0) {
            return false;
        }
        return rVar.h >= 0 || rVar.i >= 0;
    }

    @Override // traviaut.a.b
    protected final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        if (rVar.j != null && d(rVar) <= System.currentTimeMillis() + traviaut.b.r.g()) {
            lVar.a(rVar.j);
        }
    }

    @Override // traviaut.a.b
    protected final boolean b(traviaut.b.r rVar) {
        if (d(rVar) > System.currentTimeMillis()) {
            return false;
        }
        if (rVar.i >= 0) {
            return true;
        }
        if (rVar.j == null) {
            return false;
        }
        return rVar.j.e(rVar.a().c());
    }

    static {
        TimeUnit.MINUTES.toMillis(30L);
    }

    private static Element a(traviaut.e eVar, traviaut.b.h hVar, int i) {
        Element elementA = hVar.a("div", "class", "researches");
        int iA = i + 1 + (10 * eVar.e.c().a());
        NodeList elementsByTagName = elementA.getElementsByTagName("div");
        for (int i2 = 0; i2 < elementsByTagName.getLength(); i2++) {
            Element element = (Element) elementsByTagName.item(i2);
            if (element.getAttribute("class").contains("research") && !new traviaut.b.e(element).a("div", "class", "title").a("img", "class", "u" + iA).b()) {
                return element;
            }
        }
        return null;
    }

    public static boolean a(traviaut.b.r rVar, int i) throws traviaut.b.i {
        if (i == rVar.h || i == rVar.i) {
            return false;
        }
        boolean zIsEnabled = (rVar.j != null && rVar.h >= 0) ? rVar.e.troops.isEnabled(rVar.h) : false;
        if (zIsEnabled) {
            return false;
        }
        if (rVar.a().l.e(22) == 0) {
            rVar.a("no academy");
            return false;
        }
        traviaut.b.h hVarB = rVar.d.b(traviaut.b.b.b(22));
        String str = rVar.d.d()[i].c;
        Element elementA = a(rVar.d, hVarB, i);
        if (elementA == null) {
            rVar.a("troop can't be researched: " + str);
            return false;
        }
        long jD = hVarB.d();
        if (jD > 0) {
            rVar.a("research already in progress");
            rVar.i = a(hVarB);
            a(rVar, jD);
            if (rVar.i == i) {
                return true;
            }
        } else {
            a(rVar, 1L);
        }
        rVar.j = a(elementA);
        rVar.h = i;
        rVar.k = str;
        return true;
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        long j;
        Object obj;
        traviaut.b.r rVar = aVar.b;
        int i = rVar.i;
        if (i >= 0) {
            obj = null;
            rVar.f.a(t.a.values()[t.a.TROOPER1.ordinal() + rVar.d.d()[i].b], 0L, false);
        }
        rVar.i = -1;
        if (rVar.h >= 0 && rVar.j.e(rVar.a().c())) {
            rVar.a(false);
            traviaut.b.h hVarB = rVar.d.b(traviaut.b.b.b(22));
            long jD = hVarB.d();
            Element elementA = a(rVar.d, hVarB, rVar.h);
            if (elementA == null) {
                rVar.a("troop can't be researched: " + rVar.k);
                rVar.h = -1;
                j = jD;
                obj = "troop can't be researched: ";
            } else if (jD > 0) {
                rVar.a("academy research already in progress");
                rVar.i = a(hVarB);
                j = jD;
            } else {
                long jB = traviaut.m.b(new traviaut.b.e(elementA).a("span", "class", "clocks").c());
                String strC = new traviaut.b.e(elementA).a("div", "class", "contractLink").a("button").c("onclick");
                if (strC == null) {
                    rVar.a("no research link for " + rVar.k);
                    j = 0;
                    obj = "no research link for ";
                } else {
                    String[] strArrSplit = strC.split("'");
                    if (strArrSplit.length < 3) {
                        rVar.a("wrong research link for type: " + rVar.k);
                        j = 0;
                        obj = "wrong research link for type: ";
                    } else {
                        rVar.a("running research: " + rVar.k);
                        traviaut.b.h hVarB2 = rVar.d.b(strArrSplit[1]);
                        rVar.i = rVar.h;
                        rVar.h = -1;
                        rVar.k = null;
                        rVar.j = null;
                        traviaut.b.h hVar = hVarB2;
                        rVar.a(new traviaut.b.j(hVar).a());
                        j = jB;
                        obj = hVar;
                    }
                }
            }
            long j2 = j;
            if (j == 0) {
                rVar.h = -1;
            } else {
                a(rVar, j2);
            }
            rVar.f();
        }
    }
}
