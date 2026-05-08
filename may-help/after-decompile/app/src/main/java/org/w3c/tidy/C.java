package org.w3c.tidy;

import org.w3c.dom.Node;
import traviaut.xml.TAData;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/C.class */
public final class C implements Cloneable {
    private static final String[] q = {"RootNode", "DocTypeTag", "CommentTag", "ProcInsTag", "TextNode", "StartTag", "EndTag", "StartEndTag", "SectionTag", "AspTag", "PhpTag", "XmlDecl"};
    protected C a;
    protected C b;
    protected C c;
    protected C d;
    protected int e;
    protected int f;
    protected byte[] g;
    protected short h;
    protected boolean i;
    protected boolean j;
    protected boolean k;
    protected v l;
    protected v m;
    protected String n;
    protected C0003b o;
    public C p;
    private Node r;

    public C() {
        this((short) 4, null, 0, 0);
    }

    public C(short s, byte[] bArr, int i, int i2) {
        this.a = null;
        this.b = null;
        this.c = null;
        this.d = null;
        this.e = i;
        this.f = i2;
        this.g = bArr;
        this.h = s;
        this.j = false;
        this.k = false;
        this.l = null;
        this.m = null;
        this.n = null;
        this.o = null;
        this.p = null;
    }

    public C(short s, byte[] bArr, int i, int i2, String str, Q q2) {
        this.a = null;
        this.b = null;
        this.c = null;
        this.d = null;
        this.e = i;
        this.f = i2;
        this.g = bArr;
        this.h = s;
        this.j = false;
        this.k = false;
        this.l = null;
        this.m = null;
        this.n = str;
        this.o = null;
        this.p = null;
        if (s == 5 || s == 7 || s == 6) {
            q2.a(this);
        }
    }

    protected final Object clone() {
        try {
            C c = (C) super.clone();
            if (this.g != null) {
                c.g = new byte[this.f - this.e];
                c.e = 0;
                c.f = this.f - this.e;
                if (c.f > 0) {
                    System.arraycopy(this.g, this.e, c.g, c.e, c.f);
                }
            }
            if (this.o != null) {
                c.o = (C0003b) this.o.clone();
            }
            return c;
        } catch (CloneNotSupportedException e) {
            throw new RuntimeException("CloneNotSupportedException " + e.getMessage());
        }
    }

    public final C0003b a(String str) {
        C0003b c0003b;
        C0003b c0003b2 = this.o;
        while (true) {
            c0003b = c0003b2;
            if (c0003b == null || (c0003b.f != null && c0003b.f.equals(str))) {
                break;
            }
            c0003b2 = c0003b.a;
        }
        return c0003b;
    }

    public final void a(B b) {
        C0003b c0003b = this.o;
        while (true) {
            C0003b c0003b2 = c0003b;
            if (c0003b2 == null) {
                return;
            }
            c0003b2.b(b, this);
            c0003b = c0003b2.a;
        }
    }

    public final void b(B b) {
        C0003b c0003b = this.o;
        while (true) {
            C0003b c0003b2 = c0003b;
            if (c0003b2 == null) {
                return;
            }
            if (c0003b2.c == null && c0003b2.d == null) {
                C0003b c0003b3 = c0003b2.a;
                while (c0003b3 != null) {
                    if (c0003b3.c == null && c0003b3.d == null && c0003b2.f != null && c0003b2.f.equalsIgnoreCase(c0003b3.f)) {
                        if ("class".equalsIgnoreCase(c0003b3.f)) {
                            boolean z = b.z.aj;
                        }
                        if ("style".equalsIgnoreCase(c0003b3.f) && b.z.ak) {
                            int length = c0003b3.g.length() - 1;
                            if (c0003b3.g.charAt(length) == ';') {
                                c0003b3.g += " " + c0003b2.g;
                            } else if (c0003b3.g.charAt(length) == '}') {
                                c0003b3.g += " { " + c0003b2.g + " }";
                            } else {
                                c0003b3.g += "; " + c0003b2.g;
                            }
                            C0003b c0003b4 = c0003b2.a;
                            c0003b3 = c0003b4.a == null ? null : c0003b3.a;
                            b.C.a(b, this, c0003b2, (short) 68);
                            a(c0003b2);
                            c0003b2 = c0003b4;
                        } else {
                            int i = b.z.e;
                            C0003b c0003b5 = c0003b3.a;
                            b.C.a(b, this, c0003b3, (short) 55);
                            a(c0003b3);
                            c0003b3 = c0003b5;
                        }
                    } else {
                        c0003b3 = c0003b3.a;
                    }
                }
                c0003b = c0003b2.a;
            } else {
                c0003b = c0003b2.a;
            }
        }
    }

    public final void a(String str, String str2) {
        C0003b c0003b = new C0003b(null, null, null, null, 34, str, str2);
        c0003b.b = C0007f.a().a(c0003b);
        if (this.o == null) {
            this.o = c0003b;
            return;
        }
        C0003b c0003b2 = this.o;
        while (true) {
            C0003b c0003b3 = c0003b2;
            if (c0003b3.a == null) {
                c0003b3.a = c0003b;
                return;
            }
            c0003b2 = c0003b3.a;
        }
    }

    public final void a(C0003b c0003b) {
        C0003b c0003b2 = null;
        C0003b c0003b3 = this.o;
        while (true) {
            C0003b c0003b4 = c0003b3;
            if (c0003b4 == null) {
                return;
            }
            C0003b c0003b5 = c0003b4.a;
            if (c0003b4 != c0003b) {
                c0003b2 = c0003b4;
            } else if (c0003b2 != null) {
                c0003b2.a = c0003b5;
            } else {
                this.o = c0003b5;
            }
            c0003b3 = c0003b5;
        }
    }

    public final C a() {
        C c;
        C c2 = this.p;
        while (true) {
            c = c2;
            if (c == null || c.h == 1) {
                break;
            }
            c2 = c.c;
        }
        return c;
    }

    public static C a(C c) {
        C c2 = null;
        if (c != null) {
            c2 = c.c;
            c.c();
        }
        return c2;
    }

    public final void b(C c) {
        c.a = this;
        if (this.p == null) {
            this.d = c;
        } else {
            this.p.b = c;
        }
        c.c = this.p;
        c.b = null;
        this.p = c;
    }

    public final void c(C c) {
        c.a = this;
        c.b = this.d;
        if (this.d != null) {
            this.d.c = c;
        } else {
            this.p = c;
        }
        this.d = c;
    }

    public static void a(C c, C c2) {
        c2.p = c;
        c2.d = c;
        c2.a = c.a;
        c.a = c2;
        if (c2.a.p == c) {
            c2.a.p = c2;
        }
        if (c2.a.d == c) {
            c2.a.d = c2;
        }
        c2.b = c.b;
        c.b = null;
        if (c2.b != null) {
            c2.b.c = c2;
        }
        c2.c = c.c;
        c.c = null;
        if (c2.c != null) {
            c2.c.b = c2;
        }
    }

    public static void b(C c, C c2) {
        C c3 = c.a;
        c2.a = c3;
        c2.c = c;
        c2.b = c.b;
        c.b = c2;
        if (c2.b != null) {
            c2.b.c = c2;
        }
        if (c3 == null || c3.p != c) {
            return;
        }
        c3.p = c2;
    }

    public final void d(C c) {
        C c2 = this.a;
        c.a = c2;
        if (c2 == null || c2.d != this) {
            c.c = this.c;
            if (c.c != null) {
                c.c.b = c;
            }
        } else {
            c2.d = c;
        }
        this.c = c;
        c.b = this;
    }

    public static void a(B b, C c) {
        if (b.z.D) {
            Q q2 = b.z.ap;
            if (b.l(c)) {
                if (c.h != 4) {
                    b.C.a(b, c, (C) null, (short) 23);
                }
                a(c);
            } else if (c.m == q2.o && c.p == null) {
                C cB = b.b("br");
                a(b, c, q2.B);
                c.d(cB);
            }
        }
    }

    protected static C b(B b, C c) {
        C cA = b.a();
        cA.e = b.u;
        cA.g = c.g;
        b.a(60);
        if (c.h == 6) {
            b.a(47);
        }
        if (c.n != null) {
            b.a(c.n);
        } else if (c.h == 1) {
            b.a(33);
            b.a(68);
            b.a(79);
            b.a(67);
            b.a(84);
            b.a(89);
            b.a(80);
            b.a(69);
            b.a(32);
            for (int i = c.e; i < c.f; i++) {
                b.a((int) b.t[i]);
            }
        }
        if (c.h == 7) {
            b.a(47);
        }
        b.a(62);
        cA.f = b.u;
        return cA;
    }

    public final boolean c(B b) {
        if (this.h != 4) {
            return false;
        }
        if (this.f == this.e) {
            return true;
        }
        return this.f == this.e + 1 && b.t[this.f - 1] == 32;
    }

    public static void a(B b, C c, C c2) {
        if (c2.h == 4 && c2.g[c2.e] == 32 && c2.e < c2.f) {
            if (S.a(c.m.c & 16) && !S.a(c.m.c & 1024) && c.a.p != c) {
                C c3 = c.b;
                if (c3 == null || c3.h != 4) {
                    C cA = b.a();
                    if (c.e >= c.f) {
                        cA.e = 0;
                        cA.f = 1;
                        cA.g = new byte[1];
                    } else {
                        int i = c.e;
                        c.e = i + 1;
                        cA.e = i;
                        cA.f = c.e;
                        cA.g = c.g;
                    }
                    cA.g[cA.e] = 32;
                    cA.b = c3;
                    if (c3 != null) {
                        c3.c = cA;
                    }
                    cA.c = c;
                    c.b = cA;
                    cA.a = c.a;
                } else {
                    if (c3.g[c3.f - 1] != 32) {
                        byte[] bArr = c3.g;
                        int i2 = c3.f;
                        c3.f = i2 + 1;
                        bArr[i2] = 32;
                    }
                    c.e++;
                }
            }
            c2.e++;
        }
    }

    public final boolean a(v vVar) {
        C c = this.a;
        while (true) {
            C c2 = c;
            if (c2 == null) {
                return false;
            }
            if (c2.m == vVar) {
                return true;
            }
            c = c2.a;
        }
    }

    public static void b(B b, C c, C c2) {
        Q q2 = b.z.ap;
        b.C.a(b, c, c2, (short) 34);
        while (c.m != q2.b) {
            c = c.a;
        }
        b(c, c2);
    }

    public final C a(Q q2) {
        C c;
        C c2;
        C c3 = this.p;
        while (true) {
            c = c3;
            if (c == null || c.m == q2.b) {
                break;
            }
            c3 = c.c;
        }
        if (c == null) {
            return null;
        }
        C c4 = c.p;
        while (true) {
            c2 = c4;
            if (c2 == null || c2.m == q2.d || c2.m == q2.e) {
                break;
            }
            c4 = c2.c;
        }
        if (c2.m == q2.e) {
            C c5 = c2.p;
            while (true) {
                c2 = c5;
                if (c2 == null || c2.m == q2.h) {
                    break;
                }
                c5 = c2.c;
            }
            if (c2 != null) {
                C c6 = c2.p;
                while (true) {
                    c2 = c6;
                    if (c2 == null || c2.m == q2.d) {
                        break;
                    }
                    c6 = c2.c;
                }
            }
        }
        return c2;
    }

    public final boolean b() {
        return this.h == 5 || this.h == 7;
    }

    public static void a(C c, C c2, Q q2) {
        C c3 = c.a;
        while (true) {
            C c4 = c3;
            if (c4 == null) {
                return;
            }
            if (c4.m == q2.Z) {
                if (c4.a.p == c4) {
                    c4.a.p = c2;
                }
                c2.b = c4.b;
                c2.c = c4;
                c4.b = c2;
                c2.a = c4.a;
                if (c2.b != null) {
                    c2.b.c = c2;
                    return;
                }
                return;
            }
            c3 = c4.a;
        }
    }

    public static void d(B b, C c) {
        if (c.p == null) {
            C cB = b.b("td");
            c.c(cB);
            b.C.a(b, c, cB, (short) 12);
        }
    }

    public static void a(B b, C c, v vVar) {
        b.C.a(b, c, b.b(vVar.a), (short) 20);
        c.l = c.m;
        c.m = vVar;
        c.h = (short) 5;
        c.j = true;
        c.n = vVar.a;
    }

    public final void c() {
        if (this.b != null) {
            this.b.c = this.c;
        }
        if (this.c != null) {
            this.c.b = this.b;
        }
        if (this.a != null) {
            if (this.a.p == this) {
                this.a.p = this.c;
            }
            if (this.a.d == this) {
                this.a.d = this.b;
            }
        }
        this.a = null;
        this.b = null;
        this.c = null;
    }

    public static boolean c(C c, C c2) {
        if (c2.h != 2 && c2.h != 3 && c2.h != 8 && c2.h != 9 && c2.h != 10 && c2.h != 11 && c2.h != 12 && c2.h != 13) {
            return false;
        }
        c.c(c2);
        return true;
    }

    public final boolean d() {
        if (this.m != null) {
            return S.a(this.m.c & 1048576);
        }
        return true;
    }

    public final boolean e() {
        return this.p != null && this.p.c == null;
    }

    public final C b(Q q2) {
        C c;
        C c2 = this.p;
        while (true) {
            c = c2;
            if (c == null || c.m == q2.b) {
                break;
            }
            c2 = c.c;
        }
        return c;
    }

    public final C c(Q q2) {
        C cB = b(q2);
        C c = cB;
        if (cB != null) {
            C c2 = c.p;
            while (true) {
                c = c2;
                if (c == null || c.m == q2.c) {
                    break;
                }
                c2 = c.c;
            }
        }
        return c;
    }

    public final boolean f() {
        boolean z = false;
        if (this.b != null && this.b.c != this) {
            return false;
        }
        if (this.c != null && this.c.b != this) {
            return false;
        }
        if (this.a != null) {
            if (this.b == null && this.a.p != this) {
                return false;
            }
            if (this.c == null && this.a.d != this) {
                return false;
            }
            C c = this.a.p;
            while (true) {
                C c2 = c;
                if (c2 == null) {
                    break;
                }
                if (c2 == this) {
                    z = true;
                    break;
                }
                c = c2.c;
            }
            if (!z) {
                return false;
            }
        }
        C c3 = this.p;
        while (true) {
            C c4 = c3;
            if (c4 == null) {
                return true;
            }
            if (!c4.f()) {
                return false;
            }
            c3 = c4.c;
        }
    }

    public final void b(String str) {
        C0003b c0003bA = a("class");
        if (c0003bA != null) {
            c0003bA.g += " " + str;
        } else {
            a("class", str);
        }
    }

    public final String toString() {
        String str = "";
        C c = this;
        while (true) {
            C c2 = c;
            if (c2 == null) {
                return str;
            }
            String str2 = ((str + "[Node type=") + q[c2.h]) + ",element=";
            String str3 = c2.n != null ? str2 + c2.n : str2 + "null";
            if (c2.h == 4 || c2.h == 2 || c2.h == 3) {
                String str4 = str3 + ",text=";
                str3 = (c2.g == null || c2.e > c2.f) ? str4 + "null" : ((str4 + "\"") + S.a(c2.g, c2.e, c2.f - c2.e)) + "\"";
            }
            String str5 = str3 + ",content=";
            str = (c2.p != null ? str5 + c2.p.toString() : str5 + "null") + "]";
            if (c2.c != null) {
                str = str + ",";
            }
            c = c2.c;
        }
    }

    protected final Node g() {
        if (this.r == null) {
            switch (this.h) {
                case 0:
                    this.r = new C0015n(this);
                    break;
                case 1:
                    this.r = new C0016o(this);
                    break;
                case 2:
                    this.r = new C0014m(this);
                    break;
                case 3:
                    this.r = new C0021t(this);
                    break;
                case TAData.ACT_VER /* 4 */:
                    this.r = new u(this);
                    break;
                case 5:
                case 7:
                    this.r = new C0017p(this);
                    break;
                case 6:
                default:
                    this.r = new C0018q(this);
                    break;
                case 8:
                    this.r = new C0012k(this);
                    break;
            }
        }
        return this.r;
    }

    protected final C a(boolean z) {
        C c = (C) clone();
        if (z) {
            C c2 = this.p;
            while (true) {
                C c3 = c2;
                if (c3 == null) {
                    break;
                }
                c.c(c3.a(z));
                c2 = c3.c;
            }
        }
        return c;
    }

    protected final void a(short s) {
        this.h = s;
    }

    public final boolean h() {
        boolean z = false;
        if (this.o == null) {
            return true;
        }
        C0003b c0003b = this.o;
        while (true) {
            C0003b c0003b2 = c0003b;
            if (c0003b2 == null) {
                return z;
            }
            if (("language".equalsIgnoreCase(c0003b2.f) || "type".equalsIgnoreCase(c0003b2.f)) && "javascript".equalsIgnoreCase(c0003b2.g)) {
                z = true;
            }
            c0003b = c0003b2.a;
        }
    }

    public final boolean i() {
        if (this.h != 5) {
            return false;
        }
        return this.m == null || !S.a(this.m.c & 1);
    }

    public static void c(B b, C c) {
        byte b2;
        C c2 = c.p;
        Q q2 = b.z.ap;
        if (c2 != null && c2.h == 4 && c.m != q2.m) {
            a(b, c, c2);
        }
        C c3 = c.d;
        if (c3 == null || c3.h != 4) {
            return;
        }
        Q q3 = b.z.ap;
        if (c3 == null || c3.h != 4) {
            return;
        }
        if (c3.f > c3.e && ((b2 = b.t[c3.f - 1]) == 160 || b2 == 32)) {
            if (b2 != 160 || (c.m != q3.w && c.m != q3.x)) {
                c3.f--;
                if (S.a(c.m.c & 16) && !S.a(c.m.c & 1024)) {
                    b.l = true;
                }
            } else if (c3.f > c3.e + 1) {
                c3.f--;
            }
        }
        if (c3.e == c3.f) {
            a(b, c3);
        }
    }
}
