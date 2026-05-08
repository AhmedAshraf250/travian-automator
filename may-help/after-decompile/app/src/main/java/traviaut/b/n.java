package traviaut.b;

import java.util.Collection;
import java.util.LinkedList;
import java.util.List;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/n.class */
public final class n implements traviaut.a.k {
    private final r a;
    private final int b;
    private final int c;
    private final String d;
    private final List<r> e;
    private final m f;
    private int g;
    private int h;
    private boolean i;
    private boolean j;
    private boolean k;
    private boolean l;
    private long m;
    private String n;

    public n(r rVar, m mVar) {
        this.e = new LinkedList();
        this.l = true;
        this.a = rVar;
        this.b = this.a.b;
        this.c = this.a.c;
        this.d = this.a.a().a();
        this.f = mVar;
    }

    public n(int i, int i2, m mVar) {
        this.e = new LinkedList();
        this.l = true;
        this.a = null;
        this.b = i;
        this.c = i2;
        this.d = "[" + this.b + ":" + this.c + "]";
        this.f = mVar;
    }

    public final n a(r rVar) {
        this.e.add(rVar);
        return this;
    }

    public final n a(Collection<r> collection) {
        this.e.addAll(collection);
        return this;
    }

    public final n a() {
        this.h = traviaut.f.c().tradedistlimit;
        return this;
    }

    public final n a(int i) {
        this.g = i;
        return this;
    }

    public final n b() {
        this.i = true;
        return this;
    }

    public final n c() {
        this.j = true;
        return this;
    }

    public final n d() {
        this.k = true;
        return this;
    }

    public final n e() {
        this.l = false;
        return this;
    }

    private l b(r rVar) throws i {
        rVar.c();
        s sVarA = rVar.a();
        if (sVarA.l.e(17) == 0) {
            return null;
        }
        if (this.i && !rVar.e()) {
            return null;
        }
        l lVarH = sVarA.c().h();
        if (sVarA.e.a()) {
            lVarH.c();
        }
        l lVarA = this.f.a(lVarH, -1, rVar.h());
        if (lVarA == null || lVarA.i() == 0) {
            return null;
        }
        int i = traviaut.f.c().tradelimit;
        if (this.j && i > 0 && !this.f.a.e(lVarA)) {
            if (lVarA.a(100).e(sVarA.e.d(this.f.a.b(lVarH).f()).a(i))) {
                return null;
            }
        }
        return lVarA;
    }

    private traviaut.j a(h hVar, l lVar) throws i {
        traviaut.j jVar = new traviaut.j(hVar.c());
        jVar.a(this.b, this.c);
        jVar.a(lVar, false);
        return jVar;
    }

    private h a(r rVar, traviaut.n nVar, traviaut.j jVar) throws i {
        if (nVar.compareTo(traviaut.n.VER40a) < 0) {
            return rVar.d.a(jVar);
        }
        jVar.a("prepareMarketplace");
        this.n = "";
        org.a.c cVarA = traviaut.m.a(rVar.d.c.b(jVar));
        String strC = cVarA.c("errorMessage");
        if (strC.isEmpty()) {
            return h.a(cVarA.c("formular"));
        }
        this.n = strC;
        return null;
    }

    /* JADX WARN: Removed duplicated region for block: B:107:0x04d2 A[Catch: i -> 0x0566, TryCatch #0 {i -> 0x0566, blocks: (B:13:0x0049, B:15:0x005c, B:17:0x0069, B:19:0x0076, B:21:0x0089, B:23:0x00be, B:24:0x00d4, B:27:0x00fc, B:29:0x0127, B:40:0x01ac, B:42:0x01b7, B:47:0x01da, B:49:0x01f0, B:51:0x0207, B:54:0x0212, B:56:0x025a, B:93:0x0417, B:95:0x0442, B:103:0x04bd, B:105:0x04c8, B:106:0x04d1, B:107:0x04d2, B:109:0x04ef, B:110:0x04f7, B:112:0x0545, B:113:0x054a, B:96:0x0468, B:98:0x047f, B:99:0x0491, B:101:0x049c, B:102:0x04ae, B:63:0x0276, B:65:0x0293, B:67:0x02a1, B:79:0x035e, B:81:0x0366, B:83:0x03aa, B:85:0x03b5, B:86:0x03be, B:87:0x03bf, B:88:0x03da, B:89:0x03db, B:91:0x03ec, B:92:0x0416, B:68:0x02c2, B:70:0x02cd, B:71:0x02d6, B:72:0x02d7, B:74:0x02e4, B:76:0x02f4, B:78:0x0332, B:43:0x01c0, B:45:0x01cf, B:46:0x01d5, B:30:0x0145, B:32:0x0150, B:33:0x015f, B:35:0x016a, B:36:0x0186, B:38:0x0191, B:39:0x01a0), top: B:133:0x0049 }] */
    /* JADX WARN: Removed duplicated region for block: B:143:0x04c8 A[SYNTHETIC] */
    /* JADX WARN: Removed duplicated region for block: B:95:0x0442 A[Catch: i -> 0x0566, TryCatch #0 {i -> 0x0566, blocks: (B:13:0x0049, B:15:0x005c, B:17:0x0069, B:19:0x0076, B:21:0x0089, B:23:0x00be, B:24:0x00d4, B:27:0x00fc, B:29:0x0127, B:40:0x01ac, B:42:0x01b7, B:47:0x01da, B:49:0x01f0, B:51:0x0207, B:54:0x0212, B:56:0x025a, B:93:0x0417, B:95:0x0442, B:103:0x04bd, B:105:0x04c8, B:106:0x04d1, B:107:0x04d2, B:109:0x04ef, B:110:0x04f7, B:112:0x0545, B:113:0x054a, B:96:0x0468, B:98:0x047f, B:99:0x0491, B:101:0x049c, B:102:0x04ae, B:63:0x0276, B:65:0x0293, B:67:0x02a1, B:79:0x035e, B:81:0x0366, B:83:0x03aa, B:85:0x03b5, B:86:0x03be, B:87:0x03bf, B:88:0x03da, B:89:0x03db, B:91:0x03ec, B:92:0x0416, B:68:0x02c2, B:70:0x02cd, B:71:0x02d6, B:72:0x02d7, B:74:0x02e4, B:76:0x02f4, B:78:0x0332, B:43:0x01c0, B:45:0x01cf, B:46:0x01d5, B:30:0x0145, B:32:0x0150, B:33:0x015f, B:35:0x016a, B:36:0x0186, B:38:0x0191, B:39:0x01a0), top: B:133:0x0049 }] */
    /* JADX WARN: Removed duplicated region for block: B:96:0x0468 A[Catch: i -> 0x0566, TryCatch #0 {i -> 0x0566, blocks: (B:13:0x0049, B:15:0x005c, B:17:0x0069, B:19:0x0076, B:21:0x0089, B:23:0x00be, B:24:0x00d4, B:27:0x00fc, B:29:0x0127, B:40:0x01ac, B:42:0x01b7, B:47:0x01da, B:49:0x01f0, B:51:0x0207, B:54:0x0212, B:56:0x025a, B:93:0x0417, B:95:0x0442, B:103:0x04bd, B:105:0x04c8, B:106:0x04d1, B:107:0x04d2, B:109:0x04ef, B:110:0x04f7, B:112:0x0545, B:113:0x054a, B:96:0x0468, B:98:0x047f, B:99:0x0491, B:101:0x049c, B:102:0x04ae, B:63:0x0276, B:65:0x0293, B:67:0x02a1, B:79:0x035e, B:81:0x0366, B:83:0x03aa, B:85:0x03b5, B:86:0x03be, B:87:0x03bf, B:88:0x03da, B:89:0x03db, B:91:0x03ec, B:92:0x0416, B:68:0x02c2, B:70:0x02cd, B:71:0x02d6, B:72:0x02d7, B:74:0x02e4, B:76:0x02f4, B:78:0x0332, B:43:0x01c0, B:45:0x01cf, B:46:0x01d5, B:30:0x0145, B:32:0x0150, B:33:0x015f, B:35:0x016a, B:36:0x0186, B:38:0x0191, B:39:0x01a0), top: B:133:0x0049 }] */
    @Override // traviaut.a.k
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    public final void a(traviaut.k.a r7) throws traviaut.b.i {
        /*
            Method dump skipped, instruction units count: 1527
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: traviaut.b.n.a(traviaut.k$a):void");
    }
}
