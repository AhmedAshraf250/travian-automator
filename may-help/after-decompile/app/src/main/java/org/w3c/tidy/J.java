package org.w3c.tidy;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J.class */
public final class J {
    public static final I a = new g();
    public static final I b = new h();
    public static final I c = new t();
    public static final I d = new p();
    public static final I e = new b();
    public static final I f = new f();
    public static final I g = new i();
    public static final I h = new j();
    public static final I i = new d();
    public static final I j = new m();
    public static final I k = new a();
    public static final I l = new r();
    public static final I m = new c();
    public static final I n = new o();
    public static final I o = new n();
    public static final I p = new k();
    public static final I q = new q();
    public static final I r = new s();
    public static final I s = new e();
    public static final I t = new l();

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$a.class */
    public static class a implements I {
        /* JADX WARN: Code restructure failed: missing block: B:28:0x00b4, code lost:
        
            if ((r8.m.c & 2048) == 0) goto L33;
         */
        /* JADX WARN: Code restructure failed: missing block: B:30:0x00c2, code lost:
        
            if (r7.w.size() <= r7.x) goto L370;
         */
        /* JADX WARN: Code restructure failed: missing block: B:31:0x00c5, code lost:
        
            r7.i(null);
         */
        /* JADX WARN: Code restructure failed: missing block: B:32:0x00cd, code lost:
        
            r7.x = r13;
         */
        /* JADX WARN: Code restructure failed: missing block: B:33:0x00d3, code lost:
        
            r8.i = true;
            org.w3c.tidy.C.c(r7, r8);
            org.w3c.tidy.C.a(r7, r8);
         */
        /* JADX WARN: Code restructure failed: missing block: B:34:0x00e2, code lost:
        
            return;
         */
        @Override // org.w3c.tidy.I
        /*
            Code decompiled incorrectly, please refer to instructions dump.
            To view partially-correct code enable 'Show inconsistent code' option in preferences
        */
        public final void a(org.w3c.tidy.B r7, org.w3c.tidy.C r8, short r9) {
            /*
                Method dump skipped, instruction units count: 1931
                To view this dump change 'Code comments level' option to 'DEBUG'
            */
            throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.J.a.a(org.w3c.tidy.B, org.w3c.tidy.C, short):void");
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$b.class */
    public static class b implements I {
        /* JADX WARN: Removed duplicated region for block: B:153:0x0427  */
        @Override // org.w3c.tidy.I
        /*
            Code decompiled incorrectly, please refer to instructions dump.
            To view partially-correct code enable 'Show inconsistent code' option in preferences
        */
        public final void a(org.w3c.tidy.B r7, org.w3c.tidy.C r8, short r9) {
            /*
                Method dump skipped, instruction units count: 1109
                To view this dump change 'Code comments level' option to 'DEBUG'
            */
            throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.J.b.a(org.w3c.tidy.B, org.w3c.tidy.C, short):void");
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$c.class */
    public static class c implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            if ((c.m.c & 1) != 0) {
                return;
            }
            while (true) {
                C cA = b.a((short) 0);
                if (cA == null) {
                    return;
                }
                if (cA.m == c.m && cA.h == 6) {
                    c.i = true;
                    return;
                }
                if (cA.h == 6) {
                    if (cA.m != q.ab) {
                        C c2 = c.a;
                        while (true) {
                            C c3 = c2;
                            if (c3 == null) {
                                break;
                            }
                            if (cA.m == c3.m) {
                                b.c();
                                return;
                            }
                            c2 = c3.a;
                        }
                    } else {
                        J.c(b);
                        b.C.a(b, c, cA, (short) 8);
                    }
                }
                if (cA.h == 4) {
                    b.c();
                    return;
                }
                if (C.c(c, cA)) {
                    continue;
                } else if (cA.m == null) {
                    b.C.a(b, c, cA, (short) 8);
                } else if (cA.m != q.z) {
                    b.c();
                    return;
                } else if (cA.h == 6) {
                    b.C.a(b, c, cA, (short) 8);
                } else {
                    c.c(cA);
                    J.a(b, cA, (short) 0);
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$d.class */
    public static class d implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            if ((c.m.c & 1) != 0) {
                return;
            }
            b.v = -1;
            while (true) {
                C cA = b.a((short) 0);
                C cB = cA;
                if (cA == null) {
                    b.C.a(b, c, cB, (short) 6);
                    C.a(b, c);
                    return;
                }
                if (cB.m == c.m && cB.h == 6) {
                    c.i = true;
                    C.a(b, c);
                    return;
                }
                if (!C.c(c, cB)) {
                    if (cB.h == 4) {
                        b.c();
                        cB = b.b("dt");
                        b.C.a(b, c, cB, (short) 12);
                    }
                    if (cB.m == null) {
                        b.C.a(b, c, cB, (short) 8);
                    } else {
                        if (cB.h == 6) {
                            if (cB.m != q.ab) {
                                C c2 = c.a;
                                while (true) {
                                    C c3 = c2;
                                    if (c3 == null) {
                                        break;
                                    }
                                    if (cB.m == c3.m) {
                                        b.C.a(b, c, cB, (short) 7);
                                        b.c();
                                        C.a(b, c);
                                        return;
                                    }
                                    c2 = c3.a;
                                }
                            } else {
                                J.c(b);
                                b.C.a(b, c, cB, (short) 8);
                            }
                        }
                        if (cB.m == q.V) {
                            if (c.p != null) {
                                c.d(cB);
                            } else {
                                C.b(c, cB);
                                C.a(c);
                            }
                            J.a(b, cB, s);
                            c = b.b("dl");
                            cB.d(c);
                        } else {
                            if (cB.m != q.t && cB.m != q.u) {
                                b.c();
                                if ((cB.m.c & 24) == 0) {
                                    b.C.a(b, c, cB, (short) 11);
                                    C.a(b, c);
                                    return;
                                } else if ((cB.m.c & 16) == 0 && b.m) {
                                    C.a(b, c);
                                    return;
                                } else {
                                    cB = b.b("dd");
                                    b.C.a(b, c, cB, (short) 12);
                                }
                            }
                            if (cB.h == 6) {
                                b.C.a(b, c, cB, (short) 8);
                            } else {
                                c.c(cB);
                                J.a(b, cB, (short) 0);
                            }
                        }
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$e.class */
    public static class e implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            C cA;
            if (!b.o || (cA = b.a(s)) == null) {
                return;
            }
            if (cA.h == 6 && cA.m == c.m) {
                return;
            }
            b.C.a(b, c, cA, (short) 41);
            b.c();
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$f.class */
    public static class f implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            b.c = (short) (b.c | 16);
            while (true) {
                C cA = b.a((short) 0);
                C cB = cA;
                if (cA == null) {
                    b.C.a(b, c, cB, (short) 6);
                    return;
                }
                if (cB.m == c.m && cB.h == 6) {
                    c.i = true;
                    C.c(b, c);
                    return;
                }
                if (!C.c(c, cB)) {
                    if (cB.m == null) {
                        b.C.a(b, c, cB, (short) 8);
                    } else if ((cB.h != 5 && cB.h != 7) || cB.m == null || (cB.m.c & 4) == 0) {
                        if (cB.m == q.d) {
                            b.c();
                            cB = b.b("noframes");
                            b.C.a(b, c, cB, (short) 15);
                        }
                        if (cB.h == 5 && (cB.m.c & 8192) != 0) {
                            c.c(cB);
                            b.m = false;
                            J.a(b, cB, (short) 1);
                        } else if (cB.h != 7 || (cB.m.c & 8192) == 0) {
                            b.C.a(b, c, cB, (short) 8);
                        } else {
                            c.c(cB);
                        }
                    } else {
                        J.a(b, c, cB);
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$g.class */
    public static class g implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            C cB;
            C cB2;
            C c2 = null;
            C cB3 = null;
            b.z.p = false;
            b.A = false;
            Q q = b.z.ap;
            while (true) {
                C cA = b.a((short) 0);
                cB = cA;
                if (cA == null) {
                    cB = b.b("head");
                    break;
                }
                if (cB.m == q.c) {
                    break;
                }
                if (cB.m == c.m && cB.h == 6) {
                    b.C.a(b, c, cB, (short) 8);
                } else if (!C.c(c, cB)) {
                    b.c();
                    cB = b.b("head");
                    break;
                }
            }
            C c3 = cB;
            c.c(c3);
            J.b.a(b, c3, s);
            while (true) {
                C cA2 = b.a((short) 0);
                cB2 = cA2;
                if (cA2 != null) {
                    if (cB2.m != c.m) {
                        if (!C.c(c, cB2)) {
                            if (cB2.m != q.d) {
                                if (cB2.m != q.e) {
                                    if (cB2.m != q.h) {
                                        if (cB2.h == 5 || cB2.h == 7) {
                                            if (cB2.m != null && (cB2.m.c & 4) != 0) {
                                                J.a(b, c, cB2);
                                            } else if (c2 != null && cB2.m == q.f) {
                                                b.C.a(b, c, cB2, (short) 8);
                                            }
                                        }
                                        b.c();
                                        if (c2 == null) {
                                            cB2 = b.b("body");
                                            b.c(-17);
                                            break;
                                        }
                                        if (cB3 == null) {
                                            cB3 = b.b("noframes");
                                            c2.c(cB3);
                                        } else {
                                            b.C.a(b, c, cB2, (short) 26);
                                        }
                                        b.c(16);
                                        J.a(b, cB3, s);
                                    } else if (cB2.h != 5) {
                                        b.C.a(b, c, cB2, (short) 8);
                                    } else if (c2 == null) {
                                        b.C.a(b, c, cB2, (short) 8);
                                        cB2 = b.b("body");
                                        break;
                                    } else {
                                        if (cB3 == null) {
                                            cB3 = cB2;
                                            c2.c(cB3);
                                        }
                                        J.a(b, cB3, s);
                                    }
                                } else if (cB2.h != 5) {
                                    b.C.a(b, c, cB2, (short) 8);
                                } else {
                                    if (c2 != null) {
                                        b.C.b(b, c, cB2, (short) 18);
                                    } else {
                                        c2 = cB2;
                                    }
                                    c.c(cB2);
                                    J.a(b, cB2, s);
                                    C c4 = c2.p;
                                    while (true) {
                                        C c5 = c4;
                                        if (c5 != null) {
                                            if (c5.m == q.h) {
                                                cB3 = c5;
                                            }
                                            c4 = c5.c;
                                        }
                                    }
                                }
                            } else if (cB2.h == 5) {
                                if (c2 == null) {
                                    b.c(-17);
                                    break;
                                }
                                b.c();
                                if (cB3 == null) {
                                    cB3 = b.b("noframes");
                                    c2.c(cB3);
                                    b.C.a(b, c, cB3, (short) 15);
                                }
                                J.a(b, cB3, s);
                            } else {
                                b.C.a(b, c, cB2, (short) 8);
                            }
                        } else {
                            continue;
                        }
                    } else if (cB2.h != 5 && c2 == null) {
                        b.C.a(b, c, cB2, (short) 8);
                    } else if (cB2.h == 6) {
                        b.B = true;
                    }
                } else {
                    if (c2 == null) {
                        C cB4 = b.b("body");
                        c.c(cB4);
                        J.e.a(b, cB4, s);
                        return;
                    }
                    return;
                }
            }
            c.c(cB2);
            J.a(b, cB2, s);
            b.B = true;
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$h.class */
    public static class h implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            int i = 0;
            int i2 = 0;
            Q q = b.z.ap;
            while (true) {
                C cA = b.a((short) 0);
                if (cA != null) {
                    if (cA.m == c.m && cA.h == 6) {
                        c.i = true;
                        break;
                    }
                    if (cA.h == 4) {
                        b.C.a(b, c, cA, (short) 11);
                        b.c();
                        break;
                    }
                    if (!C.c(c, cA)) {
                        if (cA.h == 1) {
                            C.b(b, c, cA);
                        } else if (cA.m == null) {
                            b.C.a(b, c, cA, (short) 8);
                        } else if (!S.a(cA.m.c & 4)) {
                            if (b.o) {
                                b.C.a(b, c, cA, (short) 11);
                            }
                            b.c();
                        } else if (cA.h == 5 || cA.h == 7) {
                            if (cA.m == q.j) {
                                i++;
                                if (i > 1) {
                                    b.C.a(b, c, cA, (short) 38);
                                }
                            } else if (cA.m == q.k) {
                                i2++;
                                if (i2 > 1) {
                                    b.C.a(b, c, cA, (short) 38);
                                }
                            } else if (cA.m == q.Y) {
                                b.C.a(b, c, cA, (short) 11);
                            }
                            c.c(cA);
                            J.a(b, cA, (short) 0);
                        } else {
                            b.C.a(b, c, cA, (short) 8);
                        }
                    }
                } else {
                    break;
                }
            }
            if (i == 0) {
                boolean z = b.z.aa;
                b.C.a(b, c, (C) null, (short) 17);
                c.c(b.b("title"));
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$i.class */
    public static class i implements I {
        /* JADX WARN: Code restructure failed: missing block: B:208:0x05ad, code lost:
        
            r7.c();
            r7.C.a(r7, r8, r0, (short) 7);
         */
        /* JADX WARN: Code restructure failed: missing block: B:209:0x05c1, code lost:
        
            if ((r9 & 2) != 0) goto L211;
         */
        /* JADX WARN: Code restructure failed: missing block: B:210:0x05c4, code lost:
        
            org.w3c.tidy.C.c(r7, r8);
         */
        /* JADX WARN: Code restructure failed: missing block: B:211:0x05c9, code lost:
        
            org.w3c.tidy.C.a(r7, r8);
         */
        /* JADX WARN: Code restructure failed: missing block: B:212:0x05ce, code lost:
        
            return;
         */
        /* JADX WARN: Removed duplicated region for block: B:345:0x02e9 A[SYNTHETIC] */
        /* JADX WARN: Removed duplicated region for block: B:347:0x02be A[SYNTHETIC] */
        @Override // org.w3c.tidy.I
        /*
            Code decompiled incorrectly, please refer to instructions dump.
            To view partially-correct code enable 'Show inconsistent code' option in preferences
        */
        public final void a(org.w3c.tidy.B r7, org.w3c.tidy.C r8, short r9) {
            /*
                Method dump skipped, instruction units count: 2343
                To view this dump change 'Code comments level' option to 'DEBUG'
            */
            throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.J.i.a(org.w3c.tidy.B, org.w3c.tidy.C, short):void");
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$j.class */
    public static class j implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            if ((c.m.c & 1) != 0) {
                return;
            }
            b.v = -1;
            while (true) {
                C cA = b.a((short) 0);
                C c2 = cA;
                if (cA == null) {
                    if ((c.m.c & 524288) != 0) {
                        C.a(b, c, q.p);
                    }
                    b.C.a(b, c, c2, (short) 6);
                    C.a(b, c);
                    return;
                }
                if (c2.m == c.m && c2.h == 6) {
                    if ((c.m.c & 524288) != 0) {
                        C.a(b, c, q.p);
                    }
                    c.i = true;
                    C.a(b, c);
                    return;
                }
                if (!C.c(c, c2)) {
                    if (c2.h != 4 && c2.m == null) {
                        b.C.a(b, c, c2, (short) 8);
                    } else if (c2.h != 6) {
                        if (c2.m != q.s) {
                            b.c();
                            if (c2.m != null && (c2.m.c & 8) != 0 && b.m) {
                                b.C.a(b, c, c2, (short) 7);
                                C.a(b, c);
                                return;
                            } else {
                                C cB = b.b("li");
                                c2 = cB;
                                cB.a("style", "list-style: none");
                                b.C.a(b, c, c2, (short) 12);
                            }
                        }
                        c.c(c2);
                        J.a(b, c2, (short) 0);
                    } else if (c2.m == q.ab) {
                        J.c(b);
                        b.C.a(b, c, c2, (short) 8);
                    } else if (c2.m == null || (c2.m.c & 16) == 0) {
                        C c3 = c.a;
                        while (true) {
                            C c4 = c3;
                            if (c4 == null) {
                                b.C.a(b, c, c2, (short) 8);
                                break;
                            }
                            if (c2.m == c4.m) {
                                b.C.a(b, c, c2, (short) 7);
                                b.c();
                                if ((c.m.c & 524288) != 0) {
                                    C.a(b, c, q.p);
                                }
                                C.a(b, c);
                                return;
                            }
                            c3 = c4.a;
                        }
                    } else {
                        b.C.a(b, c, c2, (short) 8);
                        b.i(c2);
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$k.class */
    public static class k implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            C cB;
            Q q = b.z.ap;
            b.c = (short) (b.c | 32);
            while (true) {
                C cA = b.a((short) 0);
                cB = cA;
                if (cA == null) {
                    b.C.a(b, c, cB, (short) 6);
                    return;
                }
                if (cB.m == c.m && cB.h == 6) {
                    c.i = true;
                    C.c(b, c);
                    return;
                }
                if (cB.m == q.f || cB.m == q.e) {
                    break;
                }
                if (cB.m == q.b) {
                    if (cB.h == 5 || cB.h == 7) {
                        b.C.a(b, c, cB, (short) 8);
                    }
                } else if (!C.c(c, cB)) {
                    if (cB.m == q.d && cB.h == 5) {
                        boolean z = b.A;
                        c.c(cB);
                        J.a(b, cB, (short) 0);
                        if (z) {
                            C.a(b, cB, q.ag);
                            J.a(b, cB);
                        }
                    } else if (cB.h == 4 || !(cB.m == null || cB.h == 6)) {
                        if (b.A) {
                            C cA2 = b.D.a(q);
                            if (cB.h == 4) {
                                b.c();
                                cB = b.b("p");
                                b.C.a(b, c, cB, (short) 27);
                            }
                            cA2.c(cB);
                        } else {
                            b.c();
                            cB = b.b("body");
                            if (b.z.q) {
                                b.C.a(b, c, cB, (short) 15);
                            }
                            c.c(cB);
                        }
                        J.a(b, cB, (short) 0);
                    } else {
                        b.C.a(b, c, cB, (short) 8);
                    }
                }
            }
            C.c(b, c);
            if (cB.h == 6) {
                b.C.a(b, c, cB, (short) 8);
            } else {
                b.C.a(b, c, cB, (short) 7);
                b.c();
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$l.class */
    public static class l implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            b.v = -1;
            while (true) {
                C cA = b.a((short) 0);
                if (cA == null) {
                    return;
                }
                if (cA.m == c.m && cA.h == 6) {
                    c.i = true;
                    C.c(b, c);
                    return;
                } else if (!C.c(c, cA)) {
                    if (cA.h == 5 && (cA.m == q.L || cA.m == q.M)) {
                        if (cA.m == q.M) {
                            b.C.a(b, c, cA, (short) 19);
                        }
                        c.c(cA);
                        J.a(b, cA, (short) 1);
                    } else {
                        b.C.a(b, c, cA, (short) 8);
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$m.class */
    public static class m implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            if ((c.m.c & 1) != 0) {
                return;
            }
            if ((c.m.c & 524288) != 0) {
                C.a(b, c, q.m);
            }
            b.k(null);
            while (true) {
                C cA = b.a((short) 2);
                if (cA == null) {
                    b.C.a(b, c, cA, (short) 6);
                    C.a(b, c);
                    return;
                }
                if (cA.m == c.m && cA.h == 6) {
                    C.c(b, c);
                    c.i = true;
                    C.a(b, c);
                    return;
                }
                if (cA.m == q.b) {
                    if (cA.h == 5 || cA.h == 7) {
                        b.C.a(b, c, cA, (short) 8);
                    }
                } else if (cA.h == 4) {
                    if (c.p == null) {
                        if (cA.g[cA.e] == 10) {
                            cA.e++;
                        }
                        if (cA.e < cA.f) {
                        }
                    }
                    c.c(cA);
                } else if (!C.c(c, cA)) {
                    if (!b.n(cA)) {
                        b.C.a(b, c, cA, (short) 39);
                        c.c(C.b(b, cA));
                    } else if (cA.m == q.o) {
                        if (cA.h == 5) {
                            b.C.a(b, c, cA, (short) 14);
                            C.c(b, c);
                            C.a(b, cA, q.B);
                            c.c(cA);
                        } else {
                            b.C.a(b, c, cA, (short) 8);
                        }
                    } else if (cA.h == 5 || cA.h == 7) {
                        if (cA.m == q.B) {
                            C.c(b, c);
                        }
                        c.c(cA);
                        J.a(b, cA, (short) 2);
                    } else {
                        b.C.a(b, c, cA, (short) 8);
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$n.class */
    public static class n implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            if ((c.m.c & 1) != 0) {
                return;
            }
            while (true) {
                C cA = b.a((short) 0);
                C cB = cA;
                if (cA == null) {
                    C.a(b, c);
                    return;
                }
                if (cB.m == c.m) {
                    if (cB.h == 6) {
                        c.i = true;
                        C.d(b, c);
                        return;
                    } else {
                        b.c();
                        C.d(b, c);
                        return;
                    }
                }
                if (cB.h == 6) {
                    if (cB.m != q.ab && (cB.m == null || (cB.m.c & 24) == 0)) {
                        if (cB.m != q.w && cB.m != q.x) {
                            C c2 = c.a;
                            while (true) {
                                C c3 = c2;
                                if (c3 == null) {
                                    break;
                                }
                                if (cB.m == c3.m) {
                                    b.c();
                                    C.a(b, c);
                                    return;
                                }
                                c2 = c3.a;
                            }
                        } else {
                            b.C.a(b, c, cB, (short) 8);
                        }
                    } else {
                        if (cB.m == q.ab) {
                            J.c(b);
                        }
                        b.C.a(b, c, cB, (short) 8);
                    }
                }
                if (C.c(c, cB)) {
                    continue;
                } else if (cB.m == null && cB.h != 4) {
                    b.C.a(b, c, cB, (short) 8);
                } else if (cB.m == q.Z) {
                    b.C.a(b, c, cB, (short) 8);
                } else {
                    if (cB.m != null && (cB.m.c & 256) != 0) {
                        b.c();
                        C.a(b, c);
                        return;
                    }
                    if (cB.h == 6) {
                        b.C.a(b, c, cB, (short) 8);
                    } else {
                        if (cB.h != 6) {
                            if (cB.m == q.ab) {
                                b.c();
                                cB = b.b("td");
                                b.C.a(b, c, cB, (short) 12);
                            } else if (cB.h == 4 || (cB.m.c & 24) != 0) {
                                C.a(c, cB, q);
                                b.C.a(b, c, cB, (short) 11);
                                b.n = true;
                                if (cB.h != 4) {
                                    J.a(b, cB, (short) 0);
                                }
                                b.n = false;
                            } else if ((cB.m.c & 4) != 0) {
                                b.C.a(b, c, cB, (short) 11);
                                J.a(b, c, cB);
                            }
                        }
                        if (cB.m == q.w || cB.m == q.x) {
                            c.c(cB);
                            boolean z = b.m;
                            b.m = false;
                            J.a(b, cB, (short) 0);
                            b.m = z;
                            while (b.w.size() > b.x) {
                                b.i(null);
                            }
                        } else {
                            b.C.a(b, c, cB, (short) 11);
                        }
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$o.class */
    public static class o implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            if ((c.m.c & 1) != 0) {
                return;
            }
            while (true) {
                C cA = b.a((short) 0);
                C cB = cA;
                if (cA == null) {
                    C.a(b, c);
                    return;
                }
                if (cB.m == c.m) {
                    if (cB.h != 6) {
                        b.c();
                        return;
                    } else {
                        c.i = true;
                        C.a(b, c);
                        return;
                    }
                }
                if (cB.m == q.Z && cB.h == 6) {
                    b.c();
                    C.a(b, c);
                    return;
                }
                if (!C.c(c, cB)) {
                    if (cB.m != null || cB.h == 4) {
                        if (cB.h != 6) {
                            if (cB.m == q.w || cB.m == q.x) {
                                b.c();
                                cB = b.b("tr");
                                b.C.a(b, c, cB, (short) 12);
                            } else if (cB.h == 4 || (cB.m.c & 24) != 0) {
                                C.a(c, cB, q);
                                b.C.a(b, c, cB, (short) 11);
                                b.n = true;
                                if (cB.h != 4) {
                                    J.a(b, cB, (short) 0);
                                }
                                b.n = false;
                            } else if ((cB.m.c & 4) != 0) {
                                b.C.a(b, c, cB, (short) 11);
                                J.a(b, c, cB);
                            }
                        }
                        if (cB.h == 6) {
                            if (cB.m != q.ab && (cB.m == null || (cB.m.c & 24) == 0)) {
                                if (cB.m != q.y && cB.m != q.w && cB.m != q.x) {
                                    C c2 = c.a;
                                    while (true) {
                                        C c3 = c2;
                                        if (c3 == null) {
                                            break;
                                        }
                                        if (cB.m == c3.m) {
                                            b.c();
                                            C.a(b, c);
                                            return;
                                        }
                                        c2 = c3.a;
                                    }
                                } else {
                                    b.C.a(b, c, cB, (short) 8);
                                }
                            } else {
                                if (cB.m == q.ab) {
                                    J.c(b);
                                }
                                b.C.a(b, c, cB, (short) 8);
                            }
                        }
                        if ((cB.m.c & 256) != 0) {
                            if (cB.h != 6) {
                                b.c();
                            }
                            C.a(b, c);
                            return;
                        } else if (cB.h == 6) {
                            b.C.a(b, c, cB, (short) 8);
                        } else {
                            if (cB.m != q.y) {
                                cB = b.b("tr");
                                b.C.a(b, c, cB, (short) 12);
                                b.c();
                            }
                            c.c(cB);
                            J.a(b, cB, (short) 0);
                        }
                    } else {
                        b.C.a(b, c, cB, (short) 8);
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$p.class */
    public static class p implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            C cG = b.g(c);
            if (cG != null) {
                c.c(cG);
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$q.class */
    public static class q implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            b.v = -1;
            while (true) {
                C cA = b.a((short) 0);
                if (cA == null) {
                    b.C.a(b, c, cA, (short) 6);
                    return;
                }
                if (cA.m == c.m && cA.h == 6) {
                    c.i = true;
                    C.c(b, c);
                    return;
                } else if (!C.c(c, cA)) {
                    if (cA.h == 5 && (cA.m == q.L || cA.m == q.M || cA.m == q.X)) {
                        c.c(cA);
                        J.a(b, cA, (short) 0);
                    } else {
                        b.C.a(b, c, cA, (short) 8);
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$r.class */
    public static class r implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            Q q = b.z.ap;
            b.d();
            int i = b.x;
            b.x = b.w.size();
            while (true) {
                C cA = b.a((short) 0);
                C cB = cA;
                if (cA == null) {
                    b.C.a(b, c, cB, (short) 6);
                    C.a(b, c);
                    b.x = i;
                    return;
                }
                if (cB.m == c.m && cB.h == 6) {
                    b.x = i;
                    c.i = true;
                    C.a(b, c);
                    return;
                }
                if (!C.c(c, cB)) {
                    if (cB.m != null || cB.h == 4) {
                        if (cB.h != 6) {
                            if (cB.m == q.w || cB.m == q.x || cB.m == q.Z) {
                                b.c();
                                cB = b.b("tr");
                                b.C.a(b, c, cB, (short) 12);
                            } else if (cB.h == 4 || (cB.m.c & 24) != 0) {
                                C.b(c, cB);
                                b.C.a(b, c, cB, (short) 11);
                                b.n = true;
                                if (cB.h != 4) {
                                    J.a(b, cB, (short) 0);
                                }
                                b.n = false;
                            } else if ((cB.m.c & 4) != 0) {
                                J.a(b, c, cB);
                            }
                        }
                        if (cB.h == 6) {
                            if (cB.m == q.ab || !(cB.m == null || (cB.m.c & 24) == 0)) {
                                J.c(b);
                                b.C.a(b, c, cB, (short) 8);
                            } else if ((cB.m == null || (cB.m.c & 640) == 0) && (cB.m == null || (cB.m.c & 24) == 0)) {
                                C c2 = c.a;
                                while (true) {
                                    C c3 = c2;
                                    if (c3 == null) {
                                        break;
                                    }
                                    if (cB.m == c3.m) {
                                        b.C.a(b, c, cB, (short) 7);
                                        b.c();
                                        b.x = i;
                                        C.a(b, c);
                                        return;
                                    }
                                    c2 = c3.a;
                                }
                            } else {
                                b.C.a(b, c, cB, (short) 8);
                            }
                        }
                        if ((cB.m.c & 128) == 0) {
                            b.c();
                            b.C.a(b, c, cB, (short) 11);
                            b.x = i;
                            C.a(b, c);
                            return;
                        }
                        if (cB.h == 5 || cB.h == 7) {
                            c.c(cB);
                            J.a(b, cB, (short) 0);
                        } else {
                            b.C.a(b, c, cB, (short) 8);
                        }
                    } else {
                        b.C.a(b, c, cB, (short) 8);
                    }
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$s.class */
    public static class s implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            C cA;
            Q q = b.z.ap;
            b.v = -1;
            short s2 = c.m == q.ac ? (short) 2 : (short) 1;
            while (true) {
                cA = b.a(s2);
                if (cA == null) {
                    if ((c.m.c & 32768) == 0) {
                        b.C.a(b, c, cA, (short) 6);
                        return;
                    }
                    return;
                }
                if (cA.m == c.m && cA.h == 6) {
                    c.i = true;
                    C.c(b, c);
                    return;
                }
                if (!C.c(c, cA)) {
                    if (cA.h == 4) {
                        if (c.p == null && (s2 & 2) == 0) {
                            C.c(b, c);
                        }
                        if (cA.e < cA.f) {
                            c.c(cA);
                        }
                    } else if (cA.m == null || (cA.m.c & 16) == 0 || (cA.m.c & 1024) != 0) {
                        break;
                    } else {
                        b.C.a(b, c, cA, (short) 8);
                    }
                }
            }
            if ((c.m.c & 32768) == 0) {
                b.C.a(b, c, cA, (short) 7);
            }
            b.c();
            C.c(b, c);
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/J$t.class */
    public static class t implements I {
        @Override // org.w3c.tidy.I
        public final void a(B b, C c, short s) {
            while (true) {
                C cA = b.a((short) 1);
                if (cA == null) {
                    b.C.a(b, c, cA, (short) 6);
                    return;
                }
                if (cA.m == c.m && cA.h == 5) {
                    b.C.a(b, c, cA, (short) 24);
                    cA.h = (short) 6;
                } else {
                    if (cA.m == c.m && cA.h == 6) {
                        c.i = true;
                        C.c(b, c);
                        return;
                    }
                    if (cA.h == 4) {
                        if (c.p == null) {
                            C.a(b, c, cA);
                        }
                        if (cA.e < cA.f) {
                            c.c(cA);
                        }
                    } else if (C.c(c, cA)) {
                        continue;
                    } else {
                        if (cA.m != null) {
                            b.C.a(b, c, cA, (short) 7);
                            b.c();
                            C.c(b, c);
                            return;
                        }
                        b.C.a(b, c, cA, (short) 8);
                    }
                }
            }
        }
    }

    protected static void a(B b2, C c2, short s2) {
        if ((c2.m.c & 1) != 0) {
            b2.k = false;
        } else if ((c2.m.c & 16) == 0) {
            b2.l = false;
        }
        if (c2.m.b() == null) {
            return;
        }
        if (c2.h == 7) {
            C.a(b2, c2);
        } else {
            c2.m.b().a(b2, c2, s2);
        }
    }

    protected static void a(B b2, C c2, C c3) {
        c3.c();
        Q q2 = b2.z.ap;
        if (c3.h != 5 && c3.h != 7) {
            b2.C.a(b2, c2, c3, (short) 8);
            return;
        }
        b2.C.a(b2, c2, c3, (short) 11);
        while (c2.m != q2.b) {
            c2 = c2.a;
        }
        C c4 = c2.p;
        while (true) {
            C c5 = c4;
            if (c5 == null) {
                break;
            }
            if (c5.m == q2.c) {
                c5.c(c3);
                break;
            }
            c4 = c5.c;
        }
        if (c3.m.b() != null) {
            a(b2, c3, (short) 0);
        }
    }

    static void a(B b2, C c2) {
        c2.c();
        b2.D.a(b2.z.ap).c(c2);
    }

    public static C a(B b2) {
        C cB;
        C c2 = null;
        Q q2 = b2.z.ap;
        C cA = b2.a();
        cA.h = (short) 0;
        b2.D = cA;
        while (true) {
            C cA2 = b2.a((short) 0);
            if (cA2 == null) {
                break;
            }
            if (!C.c(cA, cA2)) {
                if (cA2.h == 1) {
                    if (c2 == null) {
                        cA.c(cA2);
                        c2 = cA2;
                    } else {
                        b2.C.a(b2, cA, cA2, (short) 8);
                    }
                } else if (cA2.h == 6) {
                    b2.C.a(b2, cA, cA2, (short) 8);
                } else {
                    if (cA2.h == 5 && cA2.m == q2.b) {
                        cB = cA2;
                    } else {
                        b2.c();
                        cB = b2.b("html");
                    }
                    if (cA.a() == null) {
                        boolean z = b2.z.aa;
                        b2.C.a(b2, (C) null, (C) null, (short) 44);
                    }
                    cA.c(cB);
                    a.a(b2, cB, (short) 0);
                }
            }
        }
        return cA;
    }

    public static boolean a(C c2, Q q2) {
        C0003b c0003b = c2.o;
        while (true) {
            C0003b c0003b2 = c0003b;
            if (c0003b2 == null) {
                if (c2.n == null) {
                    return false;
                }
                if ("pre".equalsIgnoreCase(c2.n) || "script".equalsIgnoreCase(c2.n) || "style".equalsIgnoreCase(c2.n)) {
                    return true;
                }
                return (q2 != null && q2.b(c2) == j) || "xsl:text".equalsIgnoreCase(c2.n);
            }
            if (c0003b2.f.equals("xml:space")) {
                return c0003b2.g.equals("preserve");
            }
            c0003b = c0003b2.a;
        }
    }

    private static void b(B b2, C c2, short s2) {
        if (a(c2, b2.z.ap)) {
            s2 = 2;
        }
        while (true) {
            C cA = b2.a(s2);
            if (cA != null) {
                if (cA.h == 6 && cA.n.equals(c2.n)) {
                    c2.i = true;
                    break;
                } else if (cA.h == 6) {
                    b2.C.b(b2, c2, cA, (short) 13);
                } else {
                    if (cA.h == 5) {
                        b(b2, cA, s2);
                    }
                    c2.c(cA);
                }
            } else {
                break;
            }
        }
        C c3 = c2.p;
        if (c3 != null && c3.h == 4 && s2 != 2 && c3.g[c3.e] == 32) {
            c3.e++;
            if (c3.e >= c3.f) {
                C.a(c3);
            }
        }
        C c4 = c2.d;
        if (c4 == null || c4.h != 4 || s2 == 2 || c4.g[c4.f - 1] != 32) {
            return;
        }
        c4.f--;
        if (c4.e >= c4.f) {
            C.a(c4);
        }
    }

    public static C b(B b2) {
        C cA = b2.a();
        cA.h = (short) 0;
        C c2 = null;
        b2.z.p = true;
        while (true) {
            C cA2 = b2.a((short) 0);
            if (cA2 == null) {
                break;
            }
            if (cA2.h == 6) {
                b2.C.a(b2, (C) null, cA2, (short) 13);
            } else if (!C.c(cA, cA2)) {
                if (cA2.h == 1) {
                    if (c2 == null) {
                        cA.c(cA2);
                        c2 = cA2;
                    } else {
                        b2.C.a(b2, cA, cA2, (short) 8);
                    }
                } else if (cA2.h == 7) {
                    cA.c(cA2);
                } else if (cA2.h == 5) {
                    cA.c(cA2);
                    b(b2, cA2, (short) 0);
                }
            }
        }
        if (c2 != null && !b2.c(c2)) {
            b2.C.a(b2, c2, (C) null, (short) 37);
        }
        if (b2.z.t) {
            b2.f(cA);
        }
        return cA;
    }

    static void c(B b2) {
        b2.f = (short) 1;
        b2.h = (short) (b2.h + 1);
    }
}
