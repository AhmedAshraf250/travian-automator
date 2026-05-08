package traviaut.b;

import java.util.ArrayList;
import java.util.List;
import traviaut.b.t;
import traviaut.xml.TAGlobalSets;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/s.class */
public final class s {
    public static final a a = new a("", "", Long.MAX_VALUE);
    private final String m;
    public int f;
    public a b = a;
    public a c = a;
    private traviaut.d.c n = new traviaut.d.c();
    public l d = new l();
    public l e = new l();
    public traviaut.d.a g = new traviaut.d.d(0);
    public traviaut.d.a h = new traviaut.d.d(0);
    public traviaut.d.a i = new traviaut.d.d(0);
    public int[] j = new int[11];
    public final List<b> k = new ArrayList();
    public final traviaut.b.b l = new traviaut.b.b();

    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/s$a.class */
    public static class a {
        public final traviaut.d.d a;
        public final String b;
        public final String c;
        private int d;

        public a(String str, String str2, long j) {
            this.b = str;
            this.c = str2;
            this.a = new traviaut.d.d(j);
        }

        public final int a() {
            return this.d;
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/s$b.class */
    public static class b {
        public traviaut.d.d a;
        public int b;

        public b(traviaut.d.d dVar, int i, int i2) {
            this.a = dVar;
            this.b = i;
        }
    }

    public s(String str) {
        this.m = str;
    }

    public final s a(String str) {
        if (str.isEmpty()) {
            str = this.m;
        }
        s sVar = new s(str);
        sVar.k.addAll(this.k);
        this.l.a(sVar.l);
        sVar.b = this.b;
        sVar.c = this.c;
        sVar.e.a(this.e);
        sVar.f = this.f;
        sVar.j = (int[]) this.j.clone();
        return sVar;
    }

    public final String a() {
        return this.m;
    }

    private static void a(a aVar, t tVar) {
        if (aVar.d == 0) {
            return;
        }
        tVar.a(aVar.d < 19 ? t.a.BUILD1 : t.a.BUILD2, aVar.a.b(), false);
    }

    public final void a(traviaut.n nVar) {
        this.b.d = this.l.a(this.b.b, nVar);
        this.c.d = this.l.a(this.c.b, nVar);
    }

    public final void a(String str, String str2, String str3, int i, long j) {
        long jB = traviaut.m.b(str3);
        if (jB == 0) {
            return;
        }
        traviaut.d.d dVar = new traviaut.d.d(j + jB);
        dVar.a(str + " " + str2);
        this.k.add(new b(dVar, i, traviaut.m.c(str)));
    }

    public final void a(traviaut.d.c cVar) {
        this.n = cVar;
    }

    public final traviaut.d.c b() {
        return this.n;
    }

    public final l c() {
        return this.n.a();
    }

    public final String e() {
        return a(this.j);
    }

    public static String a(int[] iArr) {
        StringBuilder sb = new StringBuilder();
        int i = 0;
        for (int i2 : iArr) {
            i += i2;
        }
        if (i == 0) {
            return "[_]";
        }
        sb.append('[');
        if (iArr[0] > 0) {
            sb.append("hero|");
        }
        for (int i3 = 1; i3 < iArr.length; i3++) {
            sb.append(iArr[i3] > 0 ? String.valueOf(iArr[i3]) : '_');
            if (i3 == 6 || i3 == 8) {
                sb.append('|');
            } else if (i3 < 10) {
                sb.append(',');
            }
        }
        sb.append(']');
        return sb.toString();
    }

    public final void a(t tVar) {
        long j;
        TAGlobalSets tAGlobalSetsC = traviaut.f.c();
        if (tAGlobalSetsC.refreshbuild) {
            a(this.b, tVar);
            a(this.c, tVar);
        }
        if (tAGlobalSetsC.refreshattack) {
            t.a aVar = t.a.ATTACK;
            if (this.k.isEmpty()) {
                j = 0;
            } else {
                long jB = Long.MAX_VALUE;
                for (b bVar : this.k) {
                    if (jB > bVar.a.b()) {
                        jB = bVar.a.b();
                    }
                }
                j = jB;
            }
            tVar.a(aVar, j, false);
        }
        this.g = new traviaut.d.d(tVar.b());
        this.h = new traviaut.d.d(tVar.c());
        this.i = new traviaut.d.d(tVar.a(t.a.CELEB));
    }

    public final l d() {
        return this.d.b(this.n.a());
    }
}
