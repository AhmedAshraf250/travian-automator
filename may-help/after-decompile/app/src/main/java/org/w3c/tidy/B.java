package org.w3c.tidy;

import java.io.PrintWriter;
import java.util.ArrayList;
import java.util.List;
import java.util.Stack;
import traviaut.xml.TAData;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/B.class */
public final class B {
    private static final a[] E = {new a("HTML 4.01", "XHTML 1.0 Strict", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd", 4), new a("HTML 4.01 Transitional", "XHTML 1.0 Transitional", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd", 8), new a("HTML 4.01 Frameset", "XHTML 1.0 Frameset", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd", 16), new a("HTML 4.0", "XHTML 1.0 Strict", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd", 4), new a("HTML 4.0 Transitional", "XHTML 1.0 Transitional", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd", 8), new a("HTML 4.0 Frameset", "XHTML 1.0 Frameset", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd", 16), new a("HTML 3.2", "XHTML 1.0 Transitional", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd", 2), new a("HTML 3.2 Final", "XHTML 1.0 Transitional", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd", 2), new a("HTML 3.2 Draft", "XHTML 1.0 Transitional", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd", 2), new a("HTML 2.0", "XHTML 1.0 Strict", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd", 1), new a("HTML 4.01", "XHTML 1.1", "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd", 1024)};
    protected L a;
    protected PrintWriter b;
    protected short c;
    protected short d;
    protected short e;
    protected short f;
    protected short g;
    protected short h;
    protected boolean k;
    private boolean F;
    protected boolean l;
    protected boolean m;
    protected boolean n;
    protected boolean o;
    private boolean G;
    protected int r;
    protected int s;
    private C I;
    protected byte[] t;
    private int J;
    protected int u;
    private C K;
    protected int x;
    protected M y;
    protected C0009h z;
    protected boolean A;
    protected boolean B;
    protected K C;
    protected C D;
    protected int i = 1;
    protected int j = 1;
    private short H = 0;
    protected short p = 3551;
    protected int q = 0;
    protected int v = -1;
    protected Stack<A> w = new Stack<>();
    private final List<C> L = new ArrayList();

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/B$a.class */
    static class a {
        String a;
        String b;
        short c;

        public a(String str, String str2, String str3, short s) {
            this.a = str;
            this.b = str2;
            this.c = s;
        }
    }

    public B(L l, C0009h c0009h, K k) {
        this.C = k;
        this.a = l;
        this.z = c0009h;
    }

    public final C a() {
        C c = new C();
        this.L.add(c);
        return c;
    }

    public final C a(short s, byte[] bArr, int i, int i2) {
        C c = new C(s, bArr, i, i2);
        this.L.add(c);
        return c;
    }

    public final C a(short s, byte[] bArr, int i, int i2, String str) {
        C c = new C(s, bArr, i, i2, str, this.z.ap);
        this.L.add(c);
        return c;
    }

    public final C a(C c) {
        C c2 = (C) c.clone();
        this.L.add(c2);
        C0003b c0003b = c2.o;
        while (true) {
            C0003b c0003b2 = c0003b;
            if (c0003b2 == null) {
                return c2;
            }
            if (c0003b2.c != null) {
                this.L.add(c0003b2.c);
            }
            if (c0003b2.d != null) {
                this.L.add(c0003b2.d);
            }
            c0003b = c0003b2.a;
        }
    }

    private C0003b a(C0003b c0003b) {
        C0003b c0003b2 = (C0003b) c0003b.clone();
        while (true) {
            C0003b c0003b3 = c0003b2;
            if (c0003b3 == null) {
                return c0003b2;
            }
            if (c0003b3.c != null) {
                this.L.add(c0003b3.c);
            }
            if (c0003b3.d != null) {
                this.L.add(c0003b3.d);
            }
            c0003b2 = c0003b3.a;
        }
    }

    private boolean e() {
        return this.a.d();
    }

    public final void b(int i) {
        if ((this.z.q || this.z.r) && !((i >= 32 && i <= 55295) || i == 9 || i == 10 || i == 13 || ((i >= 57344 && i <= 65533) || (i >= 65536 && i <= 1114111)))) {
            return;
        }
        int[] iArr = {0};
        byte[] bArr = new byte[10];
        if (x.a(i, bArr, null, iArr)) {
            bArr[0] = -17;
            bArr[1] = -65;
            bArr[2] = -67;
            iArr[0] = 3;
        }
        for (int i2 = 0; i2 < iArr[0]; i2++) {
            a((int) bArr[i2]);
        }
    }

    /* JADX WARN: Code restructure failed: missing block: B:18:0x0065, code lost:
    
        r5.a.a(r0);
     */
    /* JADX WARN: Code restructure failed: missing block: B:19:0x006e, code lost:
    
        return;
     */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    private void b(short r6) {
        /*
            Method dump skipped, instruction units count: 543
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.B.b(short):void");
    }

    private char f() {
        int iH;
        byte b = this.t[this.r];
        if (!this.z.p && S.g((char) b)) {
            this.t[this.r] = (byte) S.h((char) b);
        }
        while (true) {
            int iC = this.a.c();
            iH = iC;
            if (iC == -1 || !S.f((char) iH)) {
                break;
            }
            if (!this.z.p && S.g((char) iH)) {
                iH = S.h((char) iH);
            }
            b(iH);
        }
        this.s = this.u;
        return (char) iH;
    }

    public final void a(String str) {
        int length = str.length();
        for (int i = 0; i < length; i++) {
            b(str.charAt(i));
        }
    }

    private short g() {
        if (S.a(this.p & 1)) {
            return (short) 1;
        }
        if ((!(this.z.q | this.z.p) && !this.o) && S.a(this.p & 2)) {
            return (short) 2;
        }
        if (S.a(this.p & 1024)) {
            return (short) 1024;
        }
        if (S.a(this.p & 4)) {
            return (short) 4;
        }
        if (S.a(this.p & 8)) {
            return (short) 8;
        }
        return S.a(this.p & 16) ? (short) 16 : (short) 0;
    }

    public final boolean b(C c) {
        C0003b c0003bA;
        C0003b c0003bA2;
        C c2 = c.c(this.z.ap);
        if (c2 == null) {
            return false;
        }
        String str = "HTML Tidy for Java (vers. " + K.a + "), see www.w3.org";
        C c3 = c2.p;
        while (true) {
            C c4 = c3;
            if (c4 == null) {
                C cB = b("meta");
                cB.a("content", str);
                cB.a("name", "generator");
                c2.b(cB);
                return true;
            }
            if (c4.m == this.z.ap.i && (c0003bA = c4.a("name")) != null && c0003bA.g != null && "generator".equalsIgnoreCase(c0003bA.g) && (c0003bA2 = c4.a("content")) != null && c0003bA2.g != null && c0003bA2.g.length() >= 9 && "HTML Tidy".equalsIgnoreCase(c0003bA2.g.substring(0, 9))) {
                c0003bA2.g = str;
                return false;
            }
            c3 = c4.c;
        }
    }

    public final boolean c(C c) {
        int i = c.f - c.e;
        String strA = S.a(this.t, c.e, i);
        return (S.a("SYSTEM", strA, i) || S.a("PUBLIC", strA, i) || S.a("//DTD", strA, i) || S.a("//W3C", strA, i) || S.a("//EN", strA, i)) ? false : true;
    }

    private void a(C c, String str) {
        C c2;
        C0003b c0003b;
        C c3 = c.p;
        while (true) {
            c2 = c3;
            if (c2 == null || c2.m == this.z.ap.b) {
                break;
            } else {
                c3 = c2.c;
            }
        }
        if (c2 != null) {
            C0003b c0003b2 = c2.o;
            while (true) {
                c0003b = c0003b2;
                if (c0003b == null || c0003b.f.equals("xmlns")) {
                    break;
                } else {
                    c0003b2 = c0003b.a;
                }
            }
            if (c0003b == null) {
                C0003b c0003b3 = new C0003b(c2.o, null, 34, "xmlns", str);
                c0003b3.b = C0007f.a().a(c0003b3);
                c2.o = c0003b3;
            } else {
                if (c0003b.g.equals(str)) {
                    return;
                }
                this.C.a(this, c2, (C) null, (short) 33);
                c0003b.g = str;
            }
        }
    }

    private C o(C c) {
        C cB = c.b(this.z.ap);
        if (cB == null) {
            return null;
        }
        C cA = a();
        cA.a((short) 1);
        cA.c = cB;
        cA.a = c;
        cA.b = null;
        if (cB == c.p) {
            c.p.b = cA;
            c.p = cA;
            cA.b = null;
        } else {
            cA.b = cB.b;
            cA.b.c = cA;
        }
        cB.b = cA;
        return cA;
    }

    /* JADX WARN: Can't fix incorrect switch cases order, some code will duplicate */
    public final boolean e(C c) {
        short sG = 4;
        if (this.G) {
            this.C.a(this, (C) null, (C) null, (short) 35);
        }
        C cA = c.a();
        if (this.z.d == 0) {
            if (cA == null) {
                return true;
            }
            C.a(cA);
            return true;
        }
        if (this.z.q) {
            return true;
        }
        if (this.z.d == 2) {
            C.a(cA);
            cA = null;
            sG = 4;
        } else if (this.z.d == 3) {
            C.a(cA);
            cA = null;
            sG = 8;
        } else if (this.z.d == 1) {
            if (cA != null) {
                if (this.q == 0) {
                    return false;
                }
                switch (this.q) {
                    case 0:
                        break;
                    case 1:
                        if (S.a(this.p & 1)) {
                        }
                        break;
                    case 2:
                        if (S.a(this.p & 2)) {
                        }
                        break;
                    case TAData.ACT_VER /* 4 */:
                        if (S.a(this.p & 4)) {
                        }
                        break;
                    case 8:
                        if (S.a(this.p & 8)) {
                        }
                        break;
                    case 16:
                        if (S.a(this.p & 16)) {
                        }
                        break;
                    case 1024:
                        if (S.a(this.p & 1024)) {
                        }
                        break;
                }
                return true;
            }
            sG = g();
        }
        if (sG == 0) {
            return false;
        }
        if (this.z.q || this.z.p || this.o) {
            if (cA != null) {
                C.a(cA);
            }
            a(c, "http://www.w3.org/1999/xhtml");
        }
        if (cA == null) {
            C cO = o(c);
            cA = cO;
            if (cO == null) {
                return false;
            }
        }
        this.r = this.u;
        this.s = this.u;
        a("html PUBLIC ");
        if (this.z.d == 4) {
            String str = this.z.g;
        }
        if (sG == 1) {
            a("\"-//IETF//DTD HTML 2.0//EN\"");
        } else {
            a("\"-//W3C//DTD ");
            int i = 0;
            while (true) {
                if (i < 11) {
                    if (sG == E[i].c) {
                        a(E[i].a);
                    } else {
                        i++;
                    }
                }
            }
            a("//EN\"");
        }
        this.s = this.u;
        int i2 = this.s - this.r;
        cA.g = new byte[i2];
        System.arraycopy(this.t, this.r, cA.g, 0, i2);
        cA.e = 0;
        cA.f = i2;
        return true;
    }

    public final boolean f(C c) {
        C c2;
        if (c.p == null || c.p.h != 13) {
            C cA = a((short) 13, this.t, 0, 0);
            c2 = cA;
            cA.c = c.p;
            if (c.p != null) {
                c.p.b = c2;
                c2.c = c.p;
            }
            c.p = c2;
        } else {
            c2 = c.p;
        }
        C0003b c0003bA = c2.a("version");
        if (c2.a("encoding") == null && !"UTF8".equals(this.z.c())) {
            if ("ISO8859_1".equals(this.z.c())) {
                c2.a("encoding", "iso-8859-1");
            }
            if ("ISO2022".equals(this.z.c())) {
                c2.a("encoding", "iso-2022");
            }
        }
        if (c0003bA != null) {
            return true;
        }
        c2.a("version", "1.0");
        return true;
    }

    public final C b(String str) {
        C cA = a((short) 5, this.t, this.r, this.s, str);
        cA.j = true;
        return cA;
    }

    /* JADX WARN: Removed duplicated region for block: B:69:0x01c0  */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    public final org.w3c.tidy.C g(org.w3c.tidy.C r8) {
        /*
            Method dump skipped, instruction units count: 659
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.B.g(org.w3c.tidy.C):org.w3c.tidy.C");
    }

    public final void c() {
        this.F = true;
    }

    public static boolean c(String str) {
        if (str == null) {
            return false;
        }
        boolean z = true;
        int i = 0;
        int i2 = 0;
        while (z && i2 < str.length()) {
            char cCharAt = str.charAt(i2);
            if (cCharAt == '\\') {
                i = 1;
            } else if (Character.isDigit(cCharAt)) {
                if (i > 0) {
                    i++;
                    z = i < 6;
                }
                if (z) {
                    z = i2 > 0 || i > 0;
                }
            } else {
                z = i > 0 || (i2 > 0 && cCharAt == '-') || Character.isLetter(cCharAt) || (cCharAt >= 161 && cCharAt <= 255);
                i = 0;
            }
            i2++;
        }
        return z;
    }

    public final void h(C c) {
        if (c.j || c.m == null || !S.a(c.m.c & 16) || S.a(c.m.c & 2048)) {
            return;
        }
        if (c.m == this.z.ap.S || !j(c)) {
            A a2 = new A();
            a2.a = c.m;
            a2.b = c.n;
            if (c.o != null) {
                a2.c = a(c.o);
            }
            this.w.push(a2);
        }
    }

    public final void i(C c) {
        if (c != null) {
            if (c.m == null || !S.a(c.m.c & 16) || S.a(c.m.c & 2048)) {
                return;
            }
            if (c.m == this.z.ap.C) {
                while (this.w.size() > 0 && this.w.pop().a != this.z.ap.C) {
                }
                if (this.v >= this.w.size()) {
                    this.v = -1;
                    return;
                }
                return;
            }
        }
        if (this.w.size() > 0) {
            this.w.pop();
            if (this.v >= this.w.size()) {
                this.v = -1;
            }
        }
    }

    public final boolean j(C c) {
        for (int size = this.w.size() - 1; size >= 0; size--) {
            if (this.w.elementAt(size).a == c.m) {
                return true;
            }
        }
        return false;
    }

    public final int k(C c) {
        int size = this.w.size() - this.x;
        if (size > 0) {
            this.v = this.x;
            this.K = c;
        }
        return size;
    }

    public final boolean l(C c) {
        if (c.h == 4) {
            return true;
        }
        if (c.p != null) {
            return false;
        }
        if (c.m == this.z.ap.C && c.o != null) {
            return false;
        }
        if ((c.m == this.z.ap.o && !this.z.B) || c.m == null || S.a(c.m.c & 512) || S.a(c.m.c & 1) || c.m == this.z.ap.ae || c.m == this.z.ap.af) {
            return false;
        }
        return (c.m != this.z.ap.X || c.a("src") == null) && c.m != this.z.ap.j && c.m != this.z.ap.g && c.a("id") == null && c.a("name") == null;
    }

    public final void m(C c) {
        C0003b c0003bA = c.a("name");
        C0003b c0003bA2 = c.a("id");
        if (c0003bA != null) {
            if (c0003bA2 == null) {
                if (this.z.q) {
                    c.a("id", c0003bA.g);
                }
            } else {
                if (c0003bA2.g == null || c0003bA2.g.equals(c0003bA.g)) {
                    return;
                }
                this.C.a(this, c, c0003bA, (short) 60);
            }
        }
    }

    public final void d() {
        this.v = -1;
        this.K = null;
    }

    final void c(int i) {
        this.p = (short) (this.p & (i | 448));
    }

    protected final boolean n(C c) {
        if (c.m == this.z.ap.o) {
            return true;
        }
        return (c.m == null || c.m == this.z.ap.o || !S.a(c.m.c & 1048592)) ? false : true;
    }

    public final void a(int i) {
        if (this.u + 1 >= this.J) {
            while (this.u + 1 >= this.J) {
                if (this.J == 0) {
                    this.J = 8192;
                } else {
                    this.J <<= 1;
                }
            }
            byte[] bArr = this.t;
            this.t = new byte[this.J];
            if (bArr != null) {
                System.arraycopy(bArr, 0, this.t, 0, bArr.length);
                byte[] bArr2 = this.t;
                for (int i2 = 0; i2 < this.L.size(); i2++) {
                    C c = this.L.get(i2);
                    if (c.g == bArr) {
                        c.g = bArr2;
                    }
                }
            }
        }
        byte[] bArr3 = this.t;
        int i3 = this.u;
        this.u = i3 + 1;
        bArr3[i3] = (byte) i;
        this.t[this.u] = 0;
    }

    /* JADX WARN: Can't fix incorrect switch cases order, some code will duplicate */
    /* JADX WARN: Removed duplicated region for block: B:23:0x00b7  */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    public final java.lang.String b() {
        /*
            Method dump skipped, instruction units count: 263
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.B.b():java.lang.String");
    }

    public final boolean d(C c) {
        int iIndexOf;
        String str = " ";
        String str2 = "";
        String strSubstring = null;
        int i = 0;
        C cA = c.a();
        a(c, "http://www.w3.org/1999/xhtml");
        if (this.z.d == 0) {
            if (cA == null) {
                return true;
            }
            C.a(cA);
            return true;
        }
        if (this.z.d == 1) {
            if (S.a(this.p & 4)) {
                str = "-//W3C//DTD XHTML 1.0 Strict//EN";
                str2 = "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd";
            } else if (S.a(this.p & 16)) {
                str = "-//W3C//DTD XHTML 1.0 Frameset//EN";
                str2 = "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd";
            } else if (S.a(this.p & 26)) {
                str = "-//W3C//DTD XHTML 1.0 Transitional//EN";
                str2 = "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd";
            } else if (S.a(this.p & 1024)) {
                str = "-//W3C//DTD XHTML 1.1//EN";
                str2 = "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd";
            } else {
                str = null;
                str2 = "";
                if (cA != null) {
                    C.a(cA);
                }
            }
        } else if (this.z.d == 2) {
            str = "-//W3C//DTD XHTML 1.0 Strict//EN";
            str2 = "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd";
        } else if (this.z.d == 3) {
            str = "-//W3C//DTD XHTML 1.0 Transitional//EN";
            str2 = "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd";
        }
        if (this.z.d == 4) {
            String str3 = this.z.g;
        }
        if (str == null) {
            return false;
        }
        if (cA == null) {
            C cO = o(c);
            cA = cO;
            if (cO == null) {
                return false;
            }
        } else if (this.z.r || this.z.q) {
            String strA = S.a(this.t, cA.e, (cA.f - cA.e) + 1);
            int iIndexOf2 = strA.indexOf(91);
            if (iIndexOf2 >= 0 && (iIndexOf = strA.substring(iIndexOf2).indexOf(93)) >= 0) {
                i = iIndexOf + 1;
                strSubstring = strA.substring(iIndexOf2);
            }
        }
        this.r = this.u;
        this.s = this.u;
        a("html PUBLIC ");
        if (str.charAt(0) == '\"') {
            a(str);
        } else {
            a("\"");
            a(str);
            a("\"");
        }
        if (this.z.b == 0 || str2.length() + 6 < this.z.b) {
            a(" \"");
        } else {
            a("\n\"");
        }
        a(str2);
        a("\"");
        if (i > 0 && strSubstring != null) {
            b(32);
            String str4 = strSubstring;
            int i2 = i;
            int length = str4.length();
            if (length < i2) {
                i2 = length;
            }
            for (int i3 = 0; i3 < i2; i3++) {
                b(str4.charAt(i3));
            }
        }
        this.s = this.u;
        int i4 = this.s - this.r;
        cA.g = new byte[i4];
        System.arraycopy(this.t, this.r, cA.g, 0, i4);
        cA.e = 0;
        cA.f = i4;
        return false;
    }

    /* JADX WARN: Removed duplicated region for block: B:146:0x0479  */
    /* JADX WARN: Removed duplicated region for block: B:156:0x04e0  */
    /* JADX WARN: Removed duplicated region for block: B:195:0x06b7  */
    /* JADX WARN: Removed duplicated region for block: B:542:0x045f A[SYNTHETIC] */
    /* JADX WARN: Removed duplicated region for block: B:557:0x06c2 A[SYNTHETIC] */
    /* JADX WARN: Removed duplicated region for block: B:653:0x04ed A[SYNTHETIC] */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    public final org.w3c.tidy.C a(short r12) {
        /*
            Method dump skipped, instruction units count: 4615
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.B.a(short):org.w3c.tidy.C");
    }

    /* JADX WARN: Code restructure failed: missing block: B:67:0x01e4, code lost:
    
        r0 = r8.u;
        r10 = r12;
     */
    /* JADX WARN: Code restructure failed: missing block: B:69:0x01f0, code lost:
    
        if (r12 == 61) goto L113;
     */
    /* JADX WARN: Code restructure failed: missing block: B:71:0x01f7, code lost:
    
        if (r12 != 62) goto L73;
     */
    /* JADX WARN: Code restructure failed: missing block: B:72:0x01fa, code lost:
    
        r8.a.a(r12);
     */
    /* JADX WARN: Code restructure failed: missing block: B:74:0x020a, code lost:
    
        if (r12 == 60) goto L115;
     */
    /* JADX WARN: Code restructure failed: missing block: B:76:0x0210, code lost:
    
        if (r12 != (-1)) goto L78;
     */
    /* JADX WARN: Code restructure failed: missing block: B:77:0x0213, code lost:
    
        r8.a.a(r12);
     */
    /* JADX WARN: Code restructure failed: missing block: B:79:0x0222, code lost:
    
        if (r10 != 45) goto L85;
     */
    /* JADX WARN: Code restructure failed: missing block: B:81:0x0229, code lost:
    
        if (r12 == 34) goto L117;
     */
    /* JADX WARN: Code restructure failed: missing block: B:83:0x0230, code lost:
    
        if (r12 != 39) goto L85;
     */
    /* JADX WARN: Code restructure failed: missing block: B:84:0x0233, code lost:
    
        r8.u--;
        r8.a.a(r12);
     */
    /* JADX WARN: Code restructure failed: missing block: B:86:0x024f, code lost:
    
        if (org.w3c.tidy.S.c((char) r12) != false) goto L119;
     */
    /* JADX WARN: Code restructure failed: missing block: B:88:0x0259, code lost:
    
        if (r8.z.p != false) goto L121;
     */
    /* JADX WARN: Code restructure failed: missing block: B:90:0x0262, code lost:
    
        if (org.w3c.tidy.S.g((char) r12) == false) goto L122;
     */
    /* JADX WARN: Code restructure failed: missing block: B:91:0x0265, code lost:
    
        r12 = org.w3c.tidy.S.h((char) r12);
     */
    /* JADX WARN: Code restructure failed: missing block: B:92:0x026d, code lost:
    
        b(r12);
        r10 = r12;
        r12 = r8.a.c();
     */
    /* JADX WARN: Code restructure failed: missing block: B:93:0x0282, code lost:
    
        r0 = r8.u - r0;
     */
    /* JADX WARN: Code restructure failed: missing block: B:94:0x028a, code lost:
    
        if (r0 <= 0) goto L96;
     */
    /* JADX WARN: Code restructure failed: missing block: B:95:0x028d, code lost:
    
        r0 = org.w3c.tidy.S.a(r8.t, r0, r0);
     */
    /* JADX WARN: Code restructure failed: missing block: B:96:0x0299, code lost:
    
        r0 = null;
     */
    /* JADX WARN: Code restructure failed: missing block: B:97:0x029a, code lost:
    
        r10 = r0;
        r8.u = r0;
     */
    /* JADX WARN: Code restructure failed: missing block: B:98:0x02a1, code lost:
    
        return r10;
     */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    private java.lang.String a(boolean[] r9, org.w3c.tidy.C[] r10, org.w3c.tidy.C[] r11) {
        /*
            Method dump skipped, instruction units count: 674
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.B.a(boolean[], org.w3c.tidy.C[], org.w3c.tidy.C[]):java.lang.String");
    }

    /* JADX WARN: Code restructure failed: missing block: B:69:0x01cd, code lost:
    
        r2 = r11;
     */
    /* JADX WARN: Code restructure failed: missing block: B:90:0x0256, code lost:
    
        r8.C.a(r8, r8.I, (org.w3c.tidy.C0003b) null, (short) 59);
     */
    /* JADX WARN: Removed duplicated region for block: B:216:0x034c A[SYNTHETIC] */
    /* JADX WARN: Removed duplicated region for block: B:223:0x0326 A[SYNTHETIC] */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    private java.lang.String a(java.lang.String r9, boolean r10, boolean[] r11, int[] r12) {
        /*
            Method dump skipped, instruction units count: 1198
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.B.a(java.lang.String, boolean, boolean[], int[]):java.lang.String");
    }
}
