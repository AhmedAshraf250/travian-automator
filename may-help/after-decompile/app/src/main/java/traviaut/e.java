package traviaut;

import java.net.InetSocketAddress;
import java.net.Proxy;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.Random;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import traviaut.a.o;
import traviaut.a.s;
import traviaut.a.u;
import traviaut.a.x;
import traviaut.b.r;
import traviaut.k;
import traviaut.xml.TAAcc;
import traviaut.xml.TAHero;

/* JADX INFO: loaded from: traviaut.jar:traviaut/e.class */
public final class e implements traviaut.a.k {
    public final String a;
    private final String f;
    private final String g;
    private final String h;
    public final TAAcc b;
    public final b c;
    public final d d;
    public final a e;
    private final o i;
    private final List<u> j;

    public e(String[] strArr) {
        this(strArr[0], strArr[1], strArr[2]);
        if (strArr.length == 5) {
            a("using proxy: " + strArr[3] + " port: " + strArr[4]);
            this.c.a = new Proxy(Proxy.Type.HTTP, new InetSocketAddress(strArr[3], Integer.parseInt(strArr[4])));
        }
    }

    public final String a() {
        return this.f;
    }

    public final String b() {
        return this.g;
    }

    public final boolean equals(Object obj) {
        if (obj instanceof e) {
            return this.f.equals(((e) obj).f);
        }
        return false;
    }

    public final int hashCode() {
        return this.f.hashCode();
    }

    public final boolean a(e eVar) {
        return this.h.equals(eVar.h);
    }

    public final void a(String str) {
        g.a(this.f + str);
    }

    public final TAHero c() {
        TAHero tAHero = this.b.hero;
        return tAHero.inherit ? f.c().hero : tAHero;
    }

    public final x.a[] d() {
        return x.a(this.e.c());
    }

    public final void e() {
        this.e.a.b();
        k.a(this);
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        traviaut.b.h hVarB = b("dorf1.php");
        if (hVarB.b()) {
            a(hVarB, -1);
        }
    }

    public final void a(traviaut.b.h hVar, int i) {
        List<r> listA = org.a.a.a(hVar, this);
        if (listA.isEmpty()) {
            if (!this.e.a.a.isEmpty()) {
                return;
            } else {
                listA.add(new r(this));
            }
        }
        if (this.e.a.a(listA, i)) {
            this.e.e();
        }
    }

    public final traviaut.b.h b(String str) throws traviaut.b.i {
        traviaut.b.h hVarA = this.c.a(str);
        if (a(hVarA)) {
            return hVarA;
        }
        traviaut.b.h hVarA2 = this.c.a(str);
        if (a(hVarA2)) {
            return hVarA2;
        }
        throw new traviaut.b.i("unknown error, can't continue", 1);
    }

    public final traviaut.b.h a(j jVar) throws traviaut.b.i {
        traviaut.b.h hVarA = this.c.a(jVar);
        if (a(hVarA)) {
            return hVarA;
        }
        throw new traviaut.b.i("failed to handle command: " + jVar.a());
    }

    public e(String str, String str2, String str3) {
        this.d = new d();
        this.e = new a();
        this.i = new o();
        this.j = new ArrayList();
        str = str.endsWith("/") ? str : str + "/";
        this.c = new b(this);
        this.a = str;
        this.g = str2;
        this.h = str3;
        this.f = this.g + "@" + this.a + ": ";
        this.b = f.a(this.f);
        this.e.e();
        this.j.add(new traviaut.a.m());
        this.j.add(new traviaut.a.n());
        this.j.add(new traviaut.a.l());
        this.j.add(new traviaut.a.c());
        this.j.add(new traviaut.a.j());
        this.j.add(new s());
    }

    public final k.a a(long j, k.a aVar) {
        if (this.d.a(j)) {
            long jA = this.d.a();
            return jA < aVar.d ? new k.a(this, jA) : aVar;
        }
        if (Main.a() && !org.a.f.e() && this.i.a(this)) {
            return new k.a(this.i, this, 0L);
        }
        if (f.c().builden && Main.a() && !org.a.f.e()) {
            Iterator<u> it = this.j.iterator();
            while (it.hasNext()) {
                aVar = it.next().a(j, aVar, this);
            }
        }
        traviaut.b.o oVar = this.e.a;
        k.a aVar2 = aVar;
        if (oVar.a.isEmpty()) {
            return new k.a(this, 1L);
        }
        long j2 = aVar2.d;
        r rVar = null;
        for (r rVar2 : oVar.a) {
            long jB = rVar2.f.b();
            if (jB == 1) {
                return new k.a(rVar2, 1L);
            }
            if (jB < j2) {
                j2 = jB;
                rVar = rVar2;
            }
        }
        return j2 < aVar2.d ? new k.a(rVar, j2) : aVar2;
    }

    private boolean a(traviaut.b.h hVar) throws traviaut.b.i {
        boolean z;
        if (hVar.a()) {
            z = true;
        } else {
            traviaut.b.h hVarB = b(hVar);
            if (hVarB.a()) {
                z = false;
            } else {
                this.c.a();
                if (!b(hVarB).a()) {
                    throw new traviaut.b.i("login failed", 10);
                }
                z = false;
            }
        }
        if (!z) {
            return false;
        }
        Element elementA = hVar.a("div", "class", "fatal_error");
        String strA = elementA == null ? null : traviaut.b.h.a(elementA);
        String str = strA;
        if (strA != null) {
            throw new traviaut.b.i("error message: " + str, 10);
        }
        Element elementA2 = hVar.a("div", "id", "sysmsg");
        if (elementA2 != null) {
            String str2 = "system message: " + traviaut.b.h.a(elementA2);
            a(str2);
            NodeList elementsByTagName = elementA2.getElementsByTagName("a");
            for (int i = 0; i < elementsByTagName.getLength(); i++) {
                String attribute = ((Element) elementsByTagName.item(i)).getAttribute("href");
                if (attribute.contains("dorf1.php?ok")) {
                    this.c.a(attribute);
                }
            }
            throw new traviaut.b.i(str2, 10);
        }
        if (((Main.a.a.size() - 1) >> 1) / 5 >= 2 && new Random().nextInt(30) == 1 && !Main.a()) {
            System.exit(1);
        }
        if (hVar.a("div", "id", "recaptcha_widget") != null) {
            throw new traviaut.b.i("captcha activated", 10);
        }
        this.d.c();
        this.e.a(this, hVar);
        this.c.a(hVar);
        return true;
    }

    private traviaut.b.h b(traviaut.b.h hVar) throws traviaut.b.i {
        Element elementB = hVar.b("snd");
        Element elementB2 = elementB;
        if (elementB == null) {
            elementB2 = hVar.b("login");
        }
        if (elementB2 == null) {
            throw new traviaut.b.i("no login form");
        }
        j jVar = new j(elementB2);
        jVar.a(this.g, this.h);
        return this.c.a(jVar);
    }
}
