package org.w3c.tidy;

/* JADX INFO: renamed from: org.w3c.tidy.g, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/g.class */
public final class C0008g {
    private int a = 1;
    private Q b;

    public C0008g(Q q) {
        this.b = q;
    }

    private static String a(N n) {
        String strConcat = "";
        N n2 = n;
        while (true) {
            N n3 = n2;
            if (n3 == null) {
                break;
            }
            strConcat = strConcat.concat(n3.a).concat(": ").concat(n3.b);
            if (n3.c == null) {
                break;
            }
            strConcat = strConcat.concat("; ");
            n2 = n3.c;
        }
        return strConcat;
    }

    private static void a(B b, String str, String str2) {
        if (str2 != null) {
            b.a(str);
            b.a(" { color: ");
            b.a(str2);
            b.a(" }\n");
        }
    }

    private static void d(C c) {
        if (c.b != null) {
            c.b.c = c;
        } else {
            c.a.p = c;
        }
        if (c.c != null) {
            c.c.b = c;
        } else {
            c.a.d = c;
        }
        C c2 = c.p;
        while (true) {
            C c3 = c2;
            if (c3 == null) {
                return;
            }
            c3.a = c;
            c2 = c3.c;
        }
    }

    private static void e(C c) {
        C c2 = c.p;
        c.p = c2.p;
        c.d = c2.d;
        c2.p = null;
        C c3 = c.p;
        while (true) {
            C c4 = c3;
            if (c4 == null) {
                return;
            }
            c4.a = c;
            c3 = c4.c;
        }
    }

    private String a(String str, String str2) {
        return a(a(a((N) null, str), str2));
    }

    private boolean c(B b, C c) {
        C c2;
        if (c.m == this.b.S || (c.m.c & 528) == 0 || (c2 = c.p) == null || c2.c != null) {
            return false;
        }
        if (c2.m == this.b.E) {
            boolean z = b.z.y;
        }
        if (c2.m == this.b.F) {
            boolean z2 = b.z.y;
        }
        if (c2.m != this.b.S) {
            return false;
        }
        a(c, c2);
        a(c, c2.o);
        e(c);
        return true;
    }

    private boolean a(B b, C c, C[] cArr) {
        if (c.m != this.b.S) {
            return false;
        }
        boolean z = b.z.z;
        if (c.a.p == c && c.c == null) {
            return false;
        }
        a(c, c.o);
        C0003b c0003b = c.o;
        C0003b c0003b2 = null;
        while (c0003b != null) {
            C0003b c0003b3 = c0003b.a;
            if (c0003b.f.equals("style")) {
                c0003b.a = null;
                c0003b2 = c0003b;
            }
            c0003b = c0003b3;
        }
        c.o = c0003b2;
        c.m = this.b.ah;
        c.n = "span";
        return true;
    }

    private C e(B b, C c) {
        C c2 = c.p;
        C cE = c2;
        if (c2 != null) {
            C[] cArr = {c};
            while (cE != null) {
                cE = e(b, cE);
                if (cArr[0] != c) {
                    return cArr[0];
                }
                if (cE != null) {
                    cE = cE.c;
                }
            }
        }
        return d(b, c);
    }

    public final void b(C c) {
        while (c != null) {
            if (c.p != null) {
                b(c.p);
            }
            if (c.m != null && c.m.b() == J.h && c.e() && c.p.j) {
                e(c);
                c.n = this.b.ad.a;
                c.m = this.b.ad;
                c.j = true;
            }
            c = c.c;
        }
    }

    public final void c(C c) {
        while (c != null) {
            if (c.m == this.b.ad && c.j) {
                int i = 1;
                while (c.e() && c.p.m == this.b.ad && c.j) {
                    i++;
                    e(c);
                }
                if (c.p != null) {
                    c(c.p);
                }
                String str = "margin-left: " + new Integer(2 * i).toString() + "em";
                c.n = this.b.ag.a;
                c.m = this.b.ag;
                C0003b c0003bA = c.a("style");
                if (c0003bA == null || c0003bA.g == null) {
                    c.a("style", str);
                } else {
                    c0003bA.g = str + "; " + c0003bA.g;
                }
            } else if (c.p != null) {
                c(c.p);
            }
            c = c.c;
        }
    }

    static void b(B b, C c) {
        if (c == null) {
            return;
        }
        C c2 = null;
        C c3 = null;
        Q q = b.z.ap;
        C c4 = c.p;
        while (true) {
            C c5 = c4;
            if (c5 == null) {
                break;
            }
            if (c5.m == q.c) {
                c2 = c5;
            }
            if (c5.m == q.d) {
                c3 = c5;
            }
            c4 = c5.c;
        }
        if (c2 == null || c3 == null) {
            return;
        }
        C c6 = c2.p;
        while (true) {
            C c7 = c6;
            if (c7 == null) {
                return;
            }
            C c8 = c7.c;
            if (c7.m == q.af) {
                boolean z = false;
                C c9 = c7.p;
                while (true) {
                    C c10 = c9;
                    if (c10 != null) {
                        if ((c10.h == 4 && !c7.c(b)) || c10.m != q.K) {
                            break;
                        } else {
                            c9 = c10.c;
                        }
                    } else {
                        break;
                    }
                }
                z = true;
                if (z) {
                    c7.c();
                    c3.b(c7);
                }
            }
            c6 = c8;
        }
    }

    /* JADX WARN: Code restructure failed: missing block: B:50:0x011a, code lost:
    
        r0 = r14;
     */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    private org.w3c.tidy.N a(org.w3c.tidy.N r7, java.lang.String r8) {
        /*
            Method dump skipped, instruction units count: 301
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.C0008g.a(org.w3c.tidy.N, java.lang.String):org.w3c.tidy.N");
    }

    private void a(C c, String str) {
        C0003b c0003b;
        C0003b c0003b2 = c.o;
        while (true) {
            c0003b = c0003b2;
            if (c0003b == null || c0003b.f.equals("style")) {
                break;
            } else {
                c0003b2 = c0003b.a;
            }
        }
        if (c0003b != null) {
            c0003b.g = a(a(a((N) null, c0003b.g), str));
            return;
        }
        C0003b c0003b3 = new C0003b(c.o, null, 34, "style", str);
        c0003b3.b = C0007f.a().a(c0003b3);
        c.o = c0003b3;
    }

    private void a(C c, C c2) {
        C0003b c0003b;
        C0003b c0003b2;
        String str = null;
        C0003b c0003b3 = c2.o;
        while (true) {
            C0003b c0003b4 = c0003b3;
            if (c0003b4 == null) {
                break;
            }
            if ("class".equals(c0003b4.f)) {
                str = c0003b4.g;
                break;
            }
            c0003b3 = c0003b4.a;
        }
        String str2 = null;
        C0003b c0003b5 = c.o;
        while (true) {
            c0003b = c0003b5;
            if (c0003b == null) {
                break;
            }
            if ("class".equals(c0003b.f)) {
                str2 = c0003b.g;
                break;
            }
            c0003b5 = c0003b.a;
        }
        if (str2 != null) {
            if (str != null) {
                c0003b.g = str2 + ' ' + str;
            }
        } else if (str != null) {
            C0003b c0003b6 = new C0003b(c.o, null, 34, "class", str);
            c0003b6.b = C0007f.a().a(c0003b6);
            c.o = c0003b6;
        }
        String str3 = null;
        C0003b c0003b7 = c2.o;
        while (true) {
            C0003b c0003b8 = c0003b7;
            if (c0003b8 == null) {
                break;
            }
            if (c0003b8.f.equals("style")) {
                str3 = c0003b8.g;
                break;
            }
            c0003b7 = c0003b8.a;
        }
        String str4 = null;
        C0003b c0003b9 = c.o;
        while (true) {
            c0003b2 = c0003b9;
            if (c0003b2 == null) {
                break;
            }
            if (c0003b2.f.equals("style")) {
                str4 = c0003b2.g;
                break;
            }
            c0003b9 = c0003b2.a;
        }
        if (str4 != null) {
            if (str3 != null) {
                c0003b2.g = a(str4, str3);
            }
        } else if (str3 != null) {
            C0003b c0003b10 = new C0003b(c.o, null, 34, "style", str3);
            c0003b10.b = C0007f.a().a(c0003b10);
            c.o = c0003b10;
        }
    }

    private void b(C c, String str) {
        String str2;
        if (str == null) {
            return;
        }
        if ("6".equals(str) && c.m == this.b.o) {
            c.n = "h1";
            this.b.a(c);
            return;
        }
        if ("5".equals(str) && c.m == this.b.o) {
            c.n = "h2";
            this.b.a(c);
            return;
        }
        if ("4".equals(str) && c.m == this.b.o) {
            c.n = "h3";
            this.b.a(c);
            return;
        }
        String[] strArr = {"60%", "70%", "80%", null, "120%", "150%", "200%"};
        if (str.length() > 0 && '0' <= str.charAt(0) && str.charAt(0) <= '6') {
            str2 = strArr[str.charAt(0) - '0'];
        } else if (str.length() <= 0 || str.charAt(0) != '-') {
            if (str.length() <= 1 || '0' > str.charAt(1) || str.charAt(1) > '6') {
                str2 = "larger";
            } else {
                double d = 1.0d;
                for (int iCharAt = str.charAt(1) - '0'; iCharAt > 0; iCharAt--) {
                    d *= 1.2d;
                }
                str2 = ((int) (d * 100.0d)) + "%";
            }
        } else if (str.length() <= 1 || '0' > str.charAt(1) || str.charAt(1) > '6') {
            str2 = "smaller";
        } else {
            double d2 = 1.0d;
            for (int iCharAt2 = str.charAt(1) - '0'; iCharAt2 > 0; iCharAt2--) {
                d2 *= 0.8d;
            }
            str2 = ((int) (d2 * 100.0d)) + "%";
        }
        String str3 = str2;
        if (str2 != null) {
            a(c, "font-size: " + str3);
        }
    }

    private void a(C c, C0003b c0003b) {
        while (c0003b != null) {
            if (c0003b.f.equals("face")) {
                a(c, "font-family: " + c0003b.g);
            } else if (c0003b.f.equals("size")) {
                b(c, c0003b.g);
            } else if (c0003b.f.equals("color")) {
                a(c, "color: " + c0003b.g);
            }
            c0003b = c0003b.a;
        }
    }

    /* JADX WARN: Removed duplicated region for block: B:113:0x03e4  */
    /* JADX WARN: Removed duplicated region for block: B:127:0x040a A[SYNTHETIC] */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    private org.w3c.tidy.C d(org.w3c.tidy.B r7, org.w3c.tidy.C r8) {
        /*
            Method dump skipped, instruction units count: 1041
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.C0008g.d(org.w3c.tidy.B, org.w3c.tidy.C):org.w3c.tidy.C");
    }

    private void f(B b, C c) {
        String str;
        if (c.p != null) {
            C c2 = c.p;
            while (true) {
                C c3 = c2;
                if (c3 == null) {
                    break;
                }
                f(b, c3);
                c2 = c3.c;
            }
        }
        C0003b c0003bA = c.a("style");
        if (c0003bA != null) {
            String str2 = c.n;
            String str3 = c0003bA.g;
            M m = b.y;
            while (true) {
                M m2 = m;
                if (m2 == null) {
                    String str4 = b.z.an;
                    String str5 = b.z.an + this.a;
                    this.a++;
                    M m3 = new M(str2, str5, str3, b.y);
                    b.y = m3;
                    str = m3.b;
                    break;
                }
                if (m2.a.equals(str2) && m2.c.equals(str3)) {
                    str = m2.b;
                    break;
                }
                m = m2.d;
            }
            String str6 = str;
            C0003b c0003bA2 = c.a("class");
            if (c0003bA2 != null) {
                c0003bA2.g += " " + str6;
                c.a(c0003bA);
            } else {
                c0003bA.f = "class";
                c0003bA.g = str6;
            }
        }
    }

    public final void a(B b, C c) {
        boolean z;
        new C[1][0] = c;
        C cE = e(b, c);
        if (b.z.w) {
            return;
        }
        f(b, cE);
        if (b.y == null) {
            C cA = cE.a(b.z.ap);
            if (cA == null || (cA.a("background") == null && cA.a("bgcolor") == null && cA.a("text") == null && cA.a("link") == null && cA.a("vlink") == null && cA.a("alink") == null)) {
                z = true;
            } else {
                b.d = (short) (b.d | 16);
                z = false;
            }
            if (z) {
                return;
            }
        }
        C cA2 = b.a((short) 5, null, 0, 0, "style");
        cA2.j = true;
        C0003b c0003b = new C0003b(null, null, 34, "type", "text/css");
        c0003b.b = C0007f.a().a(c0003b);
        cA2.o = c0003b;
        C cA3 = cE.a(b.z.ap);
        b.r = b.u;
        if (cA3 != null) {
            String str = null;
            String str2 = null;
            String str3 = null;
            C0003b c0003bA = cA3.a("background");
            if (c0003bA != null) {
                str = c0003bA.g;
                c0003bA.g = null;
                cA3.a(c0003bA);
            }
            C0003b c0003bA2 = cA3.a("bgcolor");
            if (c0003bA2 != null) {
                str2 = c0003bA2.g;
                c0003bA2.g = null;
                cA3.a(c0003bA2);
            }
            C0003b c0003bA3 = cA3.a("text");
            if (c0003bA3 != null) {
                str3 = c0003bA3.g;
                c0003bA3.g = null;
                cA3.a(c0003bA3);
            }
            if (str != null || str2 != null || str3 != null) {
                b.a(" body {\n");
                if (str != null) {
                    b.a("  background-image: url(");
                    b.a(str);
                    b.a(");\n");
                }
                if (str2 != null) {
                    b.a("  background-color: ");
                    b.a(str2);
                    b.a(";\n");
                }
                if (str3 != null) {
                    b.a("  color: ");
                    b.a(str3);
                    b.a(";\n");
                }
                b.a(" }\n");
            }
            C0003b c0003bA4 = cA3.a("link");
            if (c0003bA4 != null) {
                a(b, " :link", c0003bA4.g);
                cA3.a(c0003bA4);
            }
            C0003b c0003bA5 = cA3.a("vlink");
            if (c0003bA5 != null) {
                a(b, " :visited", c0003bA5.g);
                cA3.a(c0003bA5);
            }
            C0003b c0003bA6 = cA3.a("alink");
            if (c0003bA6 != null) {
                a(b, " :active", c0003bA6.g);
                cA3.a(c0003bA6);
            }
        }
        M m = b.y;
        while (true) {
            M m2 = m;
            if (m2 == null) {
                break;
            }
            b.b(32);
            b.a(m2.a);
            b.b(46);
            b.a(m2.b);
            b.b(32);
            b.b(123);
            b.a(m2.c);
            b.b(125);
            b.b(10);
            m = m2.d;
        }
        b.s = b.u;
        cA2.c(b.a((short) 4, b.t, b.r, b.s));
        C c2 = cE.c(b.z.ap);
        if (c2 != null) {
            c2.c(cA2);
        }
    }

    public final void a(C c) {
        C[] cArr = new C[1];
        while (c != null) {
            C c2 = c.c;
            if ((c.m == this.b.E || c.m == this.b.F) && c.a != null && c.a.m == c.m) {
                cArr[0] = c2;
                C c3 = c;
                C c4 = c3.a;
                if (c3.p != null) {
                    c3.d.c = c3.c;
                    if (c3.c != null) {
                        c3.c.b = c3.d;
                        c3.d.c = c3.c;
                    } else {
                        c4.d = c3.d;
                    }
                    if (c3.b != null) {
                        c3.p.b = c3.b;
                        c3.b.c = c3.p;
                    } else {
                        c4.p = c3.p;
                    }
                    C c5 = c3.p;
                    while (true) {
                        C c6 = c5;
                        if (c6 == null) {
                            break;
                        }
                        c6.a = c4;
                        c5 = c6.c;
                    }
                    cArr[0] = c3.p;
                } else {
                    if (c3.c != null) {
                        c3.c.b = c3.b;
                    } else {
                        c4.d = c3.b;
                    }
                    if (c3.b != null) {
                        c3.b.c = c3.c;
                    } else {
                        c4.p = c3.c;
                    }
                    cArr[0] = c3.c;
                }
                c3.c = null;
                c3.p = null;
                c = cArr[0];
            } else {
                if (c.p != null) {
                    a(c.p);
                }
                c = c2;
            }
        }
    }
}
