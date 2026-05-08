package traviaut.b;

import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.Set;
import java.util.concurrent.TimeUnit;
import traviaut.Main;
import traviaut.a.v;
import traviaut.a.y;
import traviaut.b.m;
import traviaut.b.t;
import traviaut.k;
import traviaut.xml.TABuildLayout;
import traviaut.xml.TAVillage;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/r.class */
public final class r implements traviaut.a.k {
    public final int a;
    public final int b;
    public final int c;
    public final traviaut.e d;
    public final TAVillage e;
    public final t f;
    public final f<s> g;
    public int h;
    public int i;
    public l j;
    public String k;
    public long l;
    public int m;
    private static final List<traviaut.a.b> n;

    public r(traviaut.e eVar) {
        this(-1, 0, 0, eVar);
    }

    public r(int i, int i2, int i3, traviaut.e eVar) {
        this.f = new t();
        this.g = new f<>();
        this.h = -1;
        this.i = -1;
        this.m = 10;
        this.a = i;
        this.b = i2;
        this.c = i3;
        this.d = eVar;
        this.e = traviaut.f.a(this.d, this.a);
        this.g.a(new s("unknown"));
    }

    public final s a() {
        return this.g.a();
    }

    public final TABuildLayout b() {
        return this.e.builder.inherit ? traviaut.f.c().layout : this.e.builder.buildings;
    }

    public final void a(String str) {
        this.d.a(a().a() + ": " + str);
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws i {
        t.a aVarA = this.f.a();
        a(new j(c(aVarA.o), aVarA.o));
    }

    public final void c() throws i {
        s sVarA = a();
        if (!sVarA.l.b()) {
            a(new j(c(true), true).a());
        }
        if (sVarA.l.c()) {
            return;
        }
        a(new j(c(false), false));
    }

    public final void a(boolean z) throws i {
        if (this.a == this.d.e.a.a()) {
            return;
        }
        a(new j(c(z), z).a());
    }

    public final void d() {
        s sVarA = a();
        sVarA.a(this.f);
        this.g.a(sVarA);
    }

    public final boolean e() {
        if (!this.e.trader.upload) {
            return false;
        }
        if (this.e.work && this.e.trader.supplyBuild()) {
            return a().l.d() && this.f.d() && b(System.currentTimeMillis()).i() == 0;
        }
        return true;
    }

    public final void f() {
        a(System.currentTimeMillis());
    }

    private l b(long j) {
        l lVar = new l();
        if (!Main.a() || org.a.f.e()) {
            n.get(1).b(j, this, lVar);
            return lVar;
        }
        Iterator<traviaut.a.b> it = n.iterator();
        while (it.hasNext()) {
            it.next().b(j, this, lVar);
        }
        return lVar;
    }

    public final double a(r rVar) {
        return a(rVar.b, rVar.c);
    }

    public final double a(int i, int i2) {
        int i3 = this.b - i;
        int i4 = this.c - i2;
        return Math.sqrt((i3 * i3) + (i4 * i4));
    }

    public final Set<Integer> b(boolean z) {
        return new c(this, z).a();
    }

    public static long g() {
        return traviaut.f.c().downtime;
    }

    public final int h() {
        return this.d.e.a(a().l.e(28));
    }

    static {
        ArrayList arrayList = new ArrayList();
        n = arrayList;
        arrayList.add(new traviaut.a.f());
        n.add(new traviaut.a.g());
        n.add(new traviaut.a.i());
        n.add(new traviaut.a.q());
        n.add(new v());
        n.add(new traviaut.a.a());
        n.add(new y());
        n.add(new traviaut.a.h());
        n.add(new traviaut.a.d());
        n.add(new traviaut.a.p());
        n.add(new traviaut.a.r());
    }

    private h c(boolean z) throws i {
        String str;
        int i = z ? 1 : 2;
        StringBuilder sbAppend = new StringBuilder("refreshing dorf").append(i);
        long jB = this.f.b();
        r rVar = this;
        if (jB <= 1) {
            str = "";
        } else {
            long seconds = TimeUnit.MILLISECONDS.toSeconds(System.currentTimeMillis() - jB);
            rVar = rVar;
            str = seconds <= 0 ? "" : ": " + seconds + "s delay";
        }
        rVar.a(sbAppend.append(str).toString());
        String str2 = "dorf" + i + ".php";
        if (this.a > 0) {
            str2 = str2 + "?newdid=" + this.a;
        }
        return this.d.b(str2);
    }

    public final void a(j jVar) throws i {
        if (jVar.a.b()) {
            long jCurrentTimeMillis = System.currentTimeMillis();
            if (jVar.b) {
                this.f.a(jCurrentTimeMillis, jVar.b());
            }
            this.d.a(jVar.a, this.a);
            s sVarA = org.a.a.a(jVar, this, jCurrentTimeMillis);
            if (sVarA == null) {
                return;
            }
            sVarA.a(this.f);
            this.g.a(sVarA);
            this.d.e.e();
            if (jVar.c()) {
                a(jCurrentTimeMillis);
            }
            traviaut.k.a().b(this.d);
            org.a.a.a(this.d, this, jVar.a);
        }
    }

    private void a(long j) {
        boolean z;
        if (traviaut.f.c().builden && this.e.work && a().l.d()) {
            Iterator<traviaut.a.b> it = n.iterator();
            while (true) {
                if (!it.hasNext()) {
                    z = false;
                    break;
                }
                traviaut.a.b next = it.next();
                if ((Main.a() && !org.a.f.e()) || (next instanceof traviaut.a.g)) {
                    if (next.c(this)) {
                        traviaut.k.a(next, this);
                        z = true;
                        break;
                    }
                }
            }
            if (!z && this.d.e.a.a.size() >= 2 && this.e.trader.supplyBuild() && this.f.d()) {
                l lVarB = b(j);
                if (this.e.trader.tocrop) {
                    s sVarA = a();
                    l lVar = sVarA.e;
                    if (lVar.a()) {
                        int i = 1;
                        int iB = sVarA.c().b();
                        int iB2 = lVar.b();
                        if (iB + iB2 < 0) {
                            i = 8;
                        }
                        lVarB.a(new l(new int[]{0, 0, 0, (-iB2) * i}));
                    }
                }
                if (lVarB.i() != 0) {
                    s sVarA2 = a();
                    l lVarC = sVarA2.c();
                    if (lVarB.e(lVarC)) {
                        return;
                    }
                    l lVarC2 = lVarB.b(lVarC).f().g().c(sVarA2.d().b(sVarA2.e.f().a(traviaut.f.c().bslimit)).f().h());
                    if (lVarC2.i() != 0) {
                        a("res needed: " + lVarC2);
                        traviaut.k.a(new n(this, new m(m.b.EQ_TRADE).a(lVarC2)).a(this.d.e.a.a(this)).b().c().a(), this);
                    }
                }
            }
        }
    }
}
