package org.w3c.tidy;

import org.w3c.dom.Attr;

/* JADX INFO: renamed from: org.w3c.tidy.b, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/b.class */
public final class C0003b implements Cloneable {
    protected C0003b a;
    protected C0006e b;
    protected C c;
    protected C d;
    protected int e;
    protected String f;
    protected String g;
    protected Attr h;

    public C0003b() {
    }

    public C0003b(C0003b c0003b, C0006e c0006e, int i, String str, String str2) {
        this.a = c0003b;
        this.b = null;
        this.e = 34;
        this.f = str;
        this.g = str2;
    }

    public C0003b(C0003b c0003b, C0006e c0006e, C c, C c2, int i, String str, String str2) {
        this.a = c0003b;
        this.b = null;
        this.c = c;
        this.d = c2;
        this.e = i;
        this.f = str;
        this.g = str2;
    }

    protected final Object clone() {
        C0003b c0003b = null;
        try {
            c0003b = (C0003b) super.clone();
        } catch (CloneNotSupportedException unused) {
        }
        if (this.a != null) {
            c0003b.a = (C0003b) this.a.clone();
        }
        if (this.c != null) {
            c0003b.c = (C) this.c.clone();
        }
        if (this.d != null) {
            c0003b.d = (C) this.d.clone();
        }
        return c0003b;
    }

    public final boolean a() {
        C0006e c0006e = this.b;
        return c0006e != null && c0006e.a() == C0005d.g;
    }

    final void a(B b, C c) {
        if (this.g == null) {
            return;
        }
        String lowerCase = this.g.toLowerCase();
        if (this.g.equals(lowerCase)) {
            return;
        }
        if (b.o) {
            b.C.a(b, c, this, (short) 70);
        }
        if (b.o || b.z.ac) {
            this.g = lowerCase;
        }
    }

    public final C0006e b(B b, C c) {
        Q q = b.z.ap;
        C0006e c0006e = this.b;
        if (c0006e != null) {
            if (S.a(c0006e.d() & 32)) {
                if (!b.z.p && !b.z.q) {
                    b.C.a(b, c, this, (short) 57);
                }
            } else if (c0006e != C0007f.g || (c.m != q.C && c.m != q.D)) {
                b.c(c0006e.d());
            }
            if (c0006e.a() != null) {
                c0006e.a().a(b, c, this);
            } else if (S.a(this.b.d() & 448)) {
                b.C.a(b, c, this, (short) 53);
            }
        } else if (!b.z.p && c.m != null && this.c == null && (c.m == null || !S.a(c.m.b & 448))) {
            b.C.a(b, c, this, (short) 48);
        }
        return c0006e;
    }

    protected final Attr b() {
        if (this.h == null) {
            this.h = new C0010i(this);
        }
        return this.h;
    }

    public final String c() {
        return this.f;
    }

    public final C0003b d() {
        return this.a;
    }

    public final void a(C0003b c0003b) {
        this.a = c0003b;
    }
}
