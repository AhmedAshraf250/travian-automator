package traviaut;

import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import traviaut.b.a;
import traviaut.b.o;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a.class */
public class a {
    private n o;
    private a.C0001a[] p;
    private l q = l.UNKNOWN;
    private int r = 0;
    public final o a = new o();
    public final traviaut.b.f<traviaut.b.k> b = new traviaut.b.f<>();
    public boolean c = true;
    public boolean d;
    public boolean e;
    public boolean f;
    public boolean g;
    public int h;
    public int i;
    public boolean j;
    public int k;
    public int l;
    public int m;
    public long n;

    public final n a() {
        return this.o;
    }

    public final a.C0001a[] b() {
        return this.p;
    }

    public final l c() {
        return this.q;
    }

    public final int a(int i) {
        return ((this.r * this.q.d) * (100 + (this.q.e * i))) / 100;
    }

    public final traviaut.b.k d() {
        i.b();
        return this.b.a();
    }

    public final void e() {
        this.b.a(new traviaut.b.k(this.a.a));
    }

    private boolean a(traviaut.b.h hVar) {
        if (this.r > 0) {
            return false;
        }
        NodeList nodeListB = new traviaut.b.e(hVar.c("head")).b("script");
        for (int i = 0; i < nodeListB.getLength(); i++) {
            Element element = (Element) nodeListB.item(i);
            if (element.getAttribute("src").isEmpty()) {
                for (String str : traviaut.b.h.a(element).split("\n")) {
                    if (str.contains("Travian.Game.speed")) {
                        this.r = m.c(str.split("=")[1]);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /* JADX WARN: Removed duplicated region for block: B:32:0x011a  */
    /* JADX WARN: Removed duplicated region for block: B:36:0x0138  */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    protected final void a(traviaut.e r8, traviaut.b.h r9) {
        /*
            Method dump skipped, instruction units count: 987
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: traviaut.a.a(traviaut.e, traviaut.b.h):void");
    }

    public static boolean a(n nVar) {
        return nVar.compareTo(n.VER40) < 0;
    }

    public static boolean b(n nVar) {
        return nVar.compareTo(n.VER40) >= 0;
    }
}
