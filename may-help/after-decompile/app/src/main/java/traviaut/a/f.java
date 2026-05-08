package traviaut.a;

import org.w3c.dom.Element;
import traviaut.b.s;
import traviaut.k;
import traviaut.xml.TABuildPlace;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/f.class */
public final class f extends b {
    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        return rVar.e.builder.newbuilds && rVar.a().l.g() != 0;
    }

    @Override // traviaut.a.b
    public final boolean b(traviaut.b.r rVar) {
        traviaut.b.b bVar = rVar.a().l;
        return bVar.b(rVar.d) && a(rVar, bVar, true) != null;
    }

    @Override // traviaut.a.b
    public final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        traviaut.b.s sVarA = rVar.a();
        traviaut.b.b bVar = sVarA.l;
        if (!bVar.b(rVar.d)) {
            s.a aVar = sVarA.b;
            if (!rVar.d.e.c().c && aVar.a() < 19) {
                aVar = sVarA.c;
            }
            s.a aVar2 = aVar;
            if (aVar2.a() != 0 && aVar2.a.b() - j > traviaut.b.r.g()) {
                return;
            }
        }
        TABuildPlace tABuildPlaceA = a(rVar, bVar, false);
        if (tABuildPlaceA == null) {
            return;
        }
        int i = tABuildPlaceA.gid;
        traviaut.b.l lVar2 = new traviaut.b.l(200);
        if (i != 100) {
            lVar2 = rVar.d.e.b()[i].a(1);
        }
        lVar.a(lVar2);
    }

    /* JADX WARN: Removed duplicated region for block: B:30:0x00d4  */
    /* JADX WARN: Removed duplicated region for block: B:52:0x011d A[SYNTHETIC] */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    private static traviaut.xml.TABuildPlace a(traviaut.b.r r7, traviaut.b.b r8, boolean r9) {
        /*
            Method dump skipped, instruction units count: 293
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: traviaut.a.f.a(traviaut.b.r, traviaut.b.b, boolean):traviaut.xml.TABuildPlace");
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        boolean z;
        traviaut.b.r rVar = aVar.b;
        traviaut.b.b bVar = rVar.a().l;
        TABuildPlace tABuildPlaceA = a(rVar, bVar, true);
        if (tABuildPlaceA == null) {
            return;
        }
        int i = tABuildPlaceA.id;
        if (bVar.d(i).b()) {
            int iG = bVar.g();
            i = iG;
            if (iG == 0) {
                return;
            }
        }
        rVar.a(false);
        int i2 = tABuildPlaceA.gid;
        rVar.a("new building " + (i2 != 100 ? rVar.d.e.b()[i2].b : "wall"));
        for (int i3 = 1; i3 <= 3; i3++) {
            int i4 = i3;
            int i5 = i;
            String strA = traviaut.b.b.a(i5);
            if (i4 > 1) {
                strA = strA + "&category=" + i4;
            }
            Element elementA = rVar.d.b(strA).a("div", "id", "content");
            if (elementA == null) {
                z = false;
            } else {
                String strA2 = org.a.f.a(String.format("lvlzero %b %d %d", Boolean.valueOf(traviaut.a.a(rVar.d.e.a())), Integer.valueOf(i2), Integer.valueOf(i5)), elementA);
                if (strA2.isEmpty()) {
                    z = false;
                } else {
                    rVar.a(new traviaut.b.j(rVar.d.b(strA2), false));
                    z = true;
                }
            }
            if (z) {
                return;
            }
        }
        g gVar = new g();
        if (gVar.c(rVar)) {
            traviaut.k.a(gVar, rVar);
        }
    }
}
