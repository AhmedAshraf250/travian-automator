package org.w3c.tidy;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P.class */
public final class P {
    public static final O a = new f();
    public static final O b = new k();
    public static final O c = new m();
    public static final O d = new c();
    public static final O e = new g();
    public static final O f = new a();
    public static final O g = new b();
    public static final O h = new i();
    public static final O i = new l();
    public static final O j = new n();
    public static final O k = new h();
    public static final O l = new e();
    public static final O m = new d();
    public static final O n = new j();

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$a.class */
    public static class a implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            boolean z = false;
            boolean z2 = false;
            C0003b c0003b = c.o;
            while (true) {
                C0003b c0003b2 = c0003b;
                if (c0003b2 == null) {
                    break;
                }
                C0006e c0006eB = c0003b2.b(b, c);
                if (c0006eB == C0007f.d) {
                    z = true;
                } else if (c0006eB == C0007f.a) {
                    z2 = true;
                }
                c0003b = c0003b2.a;
            }
            if (!z) {
                b.c = (short) (b.c | 2);
                b.C.a(b, c, new C0003b(null, null, 34, "alt", ""), (short) 49);
            }
            if (z2) {
                return;
            }
            b.C.a(b, c, new C0003b(null, null, 34, "href", ""), (short) 49);
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$b.class */
    public static class b implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            c.a(b);
            b.m(c);
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$c.class */
    public static class c implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003b;
            String str = null;
            c.a(b);
            C0003b c0003b2 = c.o;
            while (true) {
                c0003b = c0003b2;
                if (c0003b == null) {
                    break;
                }
                if ("align".equalsIgnoreCase(c0003b.f)) {
                    str = c0003b.g;
                    break;
                }
                c0003b2 = c0003b.a;
            }
            if (str != null) {
                if ("left".equalsIgnoreCase(str) || "right".equalsIgnoreCase(str)) {
                    b.c(8);
                } else if ("top".equalsIgnoreCase(str) || "bottom".equalsIgnoreCase(str)) {
                    b.c(-4);
                } else {
                    b.C.a(b, c, c0003b, (short) 51);
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$d.class */
    public static class d implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003bA = c.a("action");
            c.a(b);
            if (c0003bA == null) {
                b.C.a(b, c, new C0003b(null, null, 34, "action", ""), (short) 49);
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$e.class */
    public static class e implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003bA = c.a("src");
            c.a(b);
            if (c0003bA != null) {
                b.C.a(b, c, c0003bA, (short) 54);
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$f.class */
    public static class f implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003bA = c.a("xmlns");
            if (c0003bA != null && "http://www.w3.org/1999/xhtml".equals(c0003bA.g)) {
                b.o = true;
                boolean z = b.z.s;
                b.z.r = true;
                b.z.q = true;
                b.z.u = false;
                b.z.v = false;
            }
            C0003b c0003b = c.o;
            while (true) {
                C0003b c0003b2 = c0003b;
                if (c0003b2 == null) {
                    return;
                }
                c0003b2.b(b, c);
                c0003b = c0003b2.a;
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$g.class */
    public static class g implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            boolean z = false;
            boolean z2 = false;
            boolean z3 = false;
            boolean z4 = false;
            boolean z5 = false;
            C0003b c0003b = c.o;
            while (true) {
                C0003b c0003b2 = c0003b;
                if (c0003b2 == null) {
                    break;
                }
                C0006e c0006eB = c0003b2.b(b, c);
                if (c0006eB == C0007f.d) {
                    z = true;
                } else if (c0006eB == C0007f.b) {
                    z2 = true;
                } else if (c0006eB == C0007f.e) {
                    z3 = true;
                } else if (c0006eB == C0007f.f) {
                    z4 = true;
                } else if (c0006eB == C0007f.h) {
                    z5 = true;
                } else if (c0006eB == C0007f.i || c0006eB == C0007f.j) {
                    b.c(-2);
                }
                c0003b = c0003b2.a;
            }
            if (!z) {
                b.c = (short) (b.c | 1);
                b.C.a(b, c, new C0003b(null, null, 34, "alt", ""), (short) 49);
                String str = b.z.f;
            }
            if (!z2 && !z5) {
                b.C.a(b, c, new C0003b(null, null, 34, "src", ""), (short) 49);
            }
            if (!z4 || z3) {
                return;
            }
            b.C.a(b, c, new C0003b(null, null, 34, "ismap", ""), (short) 56);
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$h.class */
    public static class h implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003bA = c.a("rel");
            c.a(b);
            if (c0003bA == null || c0003bA.g == null || !c0003bA.g.equals("stylesheet") || c.a("type") != null) {
                return;
            }
            b.C.a(b, c, new C0003b(null, null, 34, "type", ""), (short) 49);
            c.a("type", "text/css");
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$i.class */
    public static class i implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            c.a(b);
            b.m(c);
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$j.class */
    public static class j implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003bA = c.a("content");
            c.a(b);
            if (c0003bA == null) {
                b.C.a(b, c, new C0003b(null, null, 34, "content", ""), (short) 49);
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$k.class */
    public static class k implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            c.a(b);
            C0003b c0003bA = c.a("language");
            if (c.a("type") == null) {
                b.C.a(b, c, new C0003b(null, null, 34, "type", ""), (short) 49);
                if (c0003bA == null) {
                    c.a("type", "text/javascript");
                    return;
                }
                String str = c0003bA.g;
                if ("javascript".equalsIgnoreCase(str) || "jscript".equalsIgnoreCase(str)) {
                    c.a("type", "text/javascript");
                } else if ("vbscript".equalsIgnoreCase(str)) {
                    c.a("type", "text/vbscript");
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$l.class */
    public static class l implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003bA = c.a("type");
            c.a(b);
            if (c0003bA == null) {
                b.C.a(b, c, new C0003b(null, null, 34, "type", ""), (short) 49);
                c.a("type", "text/css");
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$m.class */
    public static class m implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            C0003b c0003bA;
            boolean z = false;
            C0003b c0003b = c.o;
            while (true) {
                C0003b c0003b2 = c0003b;
                if (c0003b2 == null) {
                    break;
                }
                if (c0003b2.b(b, c) == C0007f.c) {
                    z = true;
                }
                c0003b = c0003b2.a;
            }
            if (!z && b.q != 1 && b.q != 2) {
                b.c = (short) (b.c | 4);
            }
            if (b.z.q && (c0003bA = c.a("border")) != null && c0003bA.g == null) {
                c0003bA.g = "1";
            }
            C0003b c0003bA2 = c.a("height");
            if (c0003bA2 != null) {
                b.C.a(b, c, c0003bA2, (short) 53);
                b.p = (short) (b.p & 448);
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/P$n.class */
    public static class n implements O {
        @Override // org.w3c.tidy.O
        public final void a(B b, C c) {
            c.a(b);
            if (c.a("width") == null && c.a("height") == null) {
                return;
            }
            b.c(-5);
        }
    }
}
