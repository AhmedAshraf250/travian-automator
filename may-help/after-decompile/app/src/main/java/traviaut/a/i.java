package traviaut.a;

import org.w3c.dom.Element;
import traviaut.b.t;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/i.class */
public final class i extends b {
    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        int i = rVar.e.builder.demolishID;
        if (i < 19) {
            return false;
        }
        traviaut.b.b bVar = rVar.a().l;
        if (bVar.d(i).c != 0) {
            return bVar.e(15) >= 10;
        }
        rVar.e.builder.demolishID = 0;
        return false;
    }

    @Override // traviaut.a.b
    protected final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
    }

    @Override // traviaut.a.b
    protected final boolean b(traviaut.b.r rVar) {
        return rVar.f.a(t.a.DEMOLISH) <= System.currentTimeMillis();
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        traviaut.b.r rVar = aVar.b;
        int i = rVar.e.builder.demolishID;
        if (i < 19) {
            return;
        }
        rVar.a(false);
        traviaut.b.d dVarD = rVar.a().l.d(i);
        Element elementA = rVar.d.b(traviaut.b.b.a(26)).a("div", "id", "content");
        String[] strArrSplit = org.a.f.a("demolish", elementA).split(" ", 2);
        if (strArrSplit[0].equals("notime")) {
            rVar.a("no demolishing form, switching off: " + dVarD.a());
            rVar.e.builder.demolishID = 0;
            return;
        }
        if (strArrSplit[0].equals("time")) {
            rVar.a("demolition in progress: " + strArrSplit[1]);
            a(traviaut.m.b(strArrSplit[1]), rVar);
            return;
        }
        if (!strArrSplit[0].equals("form")) {
            rVar.a("unknown demolish result");
            return;
        }
        traviaut.j jVar = new traviaut.j(traviaut.b.h.a(elementA, "form", "class", "demolish_building"));
        jVar.a(i);
        traviaut.b.h hVarA = rVar.d.a(jVar);
        rVar.e.builder.buildings.set(i, false);
        long jD = hVarA.d();
        a(jD, rVar);
        rVar.a("demolition in progress: " + dVarD.a() + " time: " + traviaut.m.b(jD));
        if (dVarD.c <= 1) {
            rVar.a("demolition done, switching off");
            rVar.e.builder.demolishID = 0;
        }
    }

    private static void a(long j, traviaut.b.r rVar) {
        rVar.f.a(t.a.DEMOLISH, j + System.currentTimeMillis(), false);
        rVar.d();
        rVar.f();
    }
}
