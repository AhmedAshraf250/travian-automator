package org.w3c.tidy;

import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

/* JADX INFO: renamed from: org.w3c.tidy.d, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d.class */
public final class C0005d {
    public static final InterfaceC0004c a = new q();
    public static final InterfaceC0004c b = new l();
    public static final InterfaceC0004c c = new i();
    public static final InterfaceC0004c d = new f();
    public static final InterfaceC0004c e = new a();
    public static final InterfaceC0004c f = new s();
    public static final InterfaceC0004c g = new b();
    public static final InterfaceC0004c h = new h();
    public static final InterfaceC0004c i = new o();
    public static final InterfaceC0004c j = new e();
    public static final InterfaceC0004c k = new c();
    public static final InterfaceC0004c l = new n();
    public static final InterfaceC0004c m = new j();
    public static final InterfaceC0004c n = new k();
    public static final InterfaceC0004c o = new C0000d();
    public static final InterfaceC0004c p = new r();
    public static final InterfaceC0004c q = new m();
    public static final InterfaceC0004c r = new p();
    public static final InterfaceC0004c s = new g();
    public static final InterfaceC0004c t = null;
    public static final InterfaceC0004c u = null;
    public static final InterfaceC0004c v = null;
    public static final InterfaceC0004c w = null;
    public static final InterfaceC0004c x = null;
    public static final InterfaceC0004c y = null;
    public static final InterfaceC0004c z = null;
    public static final InterfaceC0004c A = null;
    public static final InterfaceC0004c B = null;
    public static final InterfaceC0004c C = null;
    public static final InterfaceC0004c D = null;
    public static final InterfaceC0004c E = null;
    public static final InterfaceC0004c F = null;
    public static final InterfaceC0004c G = null;

    /* JADX INFO: renamed from: org.w3c.tidy.d$a */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$a.class */
    public static class a implements InterfaceC0004c {
        private static final String[] a = {"left", "center", "right", "justify"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c.m != null && (c.m.c & 65536) != 0) {
                C0005d.f.a(b, c, c0003b);
                return;
            }
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            c0003b.a(b, c);
            if (S.a(a, c0003b.g)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$b */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$b.class */
    public static class b implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                return;
            }
            c0003b.a(b, c);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$c */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$c.class */
    public static class c implements InterfaceC0004c {
        private static final String[] a = {"none", "left", "right", "all"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                c0003b.g = a[0];
            } else {
                c0003b.a(b, c);
                if (S.a(a, c0003b.g)) {
                    return;
                }
                b.C.a(b, c, c0003b, (short) 51);
            }
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$d, reason: collision with other inner class name */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$d.class */
    public static class C0000d implements InterfaceC0004c {
        private static final Map<String, String> a;

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            boolean z = false;
            boolean z2 = false;
            if (c0003b.g == null || c0003b.g.length() == 0) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            String str = c0003b.g;
            Iterator<Map.Entry<String, String>> it = a.entrySet().iterator();
            while (true) {
                if (!it.hasNext()) {
                    break;
                }
                Map.Entry<String, String> next = it.next();
                if (str.charAt(0) == '#') {
                    if (str.length() == 7) {
                        if (str.equalsIgnoreCase(next.getValue())) {
                            boolean z3 = b.z.ad;
                            z2 = true;
                            break;
                        }
                    } else {
                        b.C.a(b, c, c0003b, (short) 51);
                        z = true;
                        break;
                    }
                } else if (S.e(str.charAt(0))) {
                    if (str.equalsIgnoreCase(next.getKey())) {
                        boolean z4 = b.z.ad;
                        z2 = true;
                        break;
                    }
                } else {
                    b.C.a(b, c, c0003b, (short) 51);
                    z = true;
                    break;
                }
            }
            if (z2 || z) {
                return;
            }
            if (str.charAt(0) != '#') {
                b.C.a(b, c, c0003b, (short) 51);
                return;
            }
            int i = 1;
            while (true) {
                if (i >= 7) {
                    break;
                }
                if (!S.d(str.charAt(i)) && "abcdef".indexOf(Character.toLowerCase(str.charAt(i))) == -1) {
                    b.C.a(b, c, c0003b, (short) 51);
                    z = true;
                    break;
                }
                i++;
            }
            if (z) {
                return;
            }
            for (int i2 = 1; i2 < 7; i2++) {
                c0003b.g = str.toUpperCase();
            }
        }

        static {
            HashMap map = new HashMap();
            a = map;
            map.put("black", "#000000");
            a.put("green", "#008000");
            a.put("silver", "#C0C0C0");
            a.put("lime", "#00FF00");
            a.put("gray", "#808080");
            a.put("olive", "#808000");
            a.put("white", "#FFFFFF");
            a.put("yellow", "#FFFF00");
            a.put("maroon", "#800000");
            a.put("navy", "#000080");
            a.put("red", "#FF0000");
            a.put("blue", "#0000FF");
            a.put("purple", "#800080");
            a.put("teal", "#008080");
            a.put("fuchsia", "#FF00FF");
            a.put("aqua", "#00FFFF");
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$e */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$e.class */
    public static class e implements InterfaceC0004c {
        private static final String[] a = {"get", "post"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            c0003b.a(b, c);
            if (S.a(a, c0003b.g)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$f */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$f.class */
    public static class f implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null || c0003b.g.length() == 0) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            String str = c0003b.g;
            char cCharAt = str.charAt(0);
            if (str.length() != 0 && Character.isLetter(str.charAt(0))) {
                int i = 1;
                while (true) {
                    if (i >= str.length()) {
                        break;
                    }
                    char cCharAt2 = str.charAt(i);
                    if (S.f(cCharAt2)) {
                        i++;
                    } else if (b.o && S.b(cCharAt2)) {
                        b.C.a(b, c, c0003b, (short) 71);
                    } else {
                        b.C.a(b, c, c0003b, (short) 51);
                    }
                }
            } else if (b.o && (S.a(cCharAt) || cCharAt == '_' || cCharAt == ':')) {
                b.C.a(b, c, c0003b, (short) 71);
            } else {
                b.C.a(b, c, c0003b, (short) 51);
            }
            C cA = b.z.ap.a(c0003b.g);
            if (cA != null && cA != c) {
                b.C.a(b, c, c0003b, (short) 66);
            } else {
                b.z.ap.ak = b.z.ap.a(c0003b.g, c);
            }
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$g */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$g.class */
    public static class g implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if ("lang".equals(c0003b.f)) {
                b.c(-1025);
            }
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
            }
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$h */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$h.class */
    public static class h implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            if ("width".equalsIgnoreCase(c0003b.f) && (c.m == b.z.ap.z || c.m == b.z.ap.A)) {
                return;
            }
            String str = c0003b.g;
            if (str.length() == 0 || !(Character.isDigit(str.charAt(0)) || '%' == str.charAt(0))) {
                b.C.a(b, c, c0003b, (short) 51);
                return;
            }
            Q q = b.z.ap;
            for (int i = 1; i < str.length(); i++) {
                if ((!Character.isDigit(str.charAt(i)) && (c.m == q.w || c.m == q.x)) || (!Character.isDigit(str.charAt(i)) && str.charAt(i) != '%')) {
                    b.C.a(b, c, c0003b, (short) 51);
                    return;
                }
            }
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$i */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$i.class */
    public static class i implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            if (b.z.ap.c(c)) {
                b.c(-1025);
                C cA = b.z.ap.a(c0003b.g);
                if (cA != null && cA != c) {
                    b.C.a(b, c, c0003b, (short) 66);
                } else {
                    b.z.ap.ak = b.z.ap.a(c0003b.g, c);
                }
            }
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$j */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$j.class */
    public static class j implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            if (("cols".equalsIgnoreCase(c0003b.f) || "rows".equalsIgnoreCase(c0003b.f)) && c.m == b.z.ap.e) {
                return;
            }
            String str = c0003b.g;
            int i = 0;
            if (c.m == b.z.ap.S && (str.startsWith("+") || str.startsWith("-"))) {
                i = 0 + 1;
            }
            while (i < str.length()) {
                if (!Character.isDigit(str.charAt(i))) {
                    b.C.a(b, c, c0003b, (short) 51);
                    return;
                }
                i++;
            }
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$k */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$k.class */
    public static class k implements InterfaceC0004c {
        private static final String[] a = {"row", "rowgroup", "col", "colgroup"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            c0003b.a(b, c);
            if (S.a(a, c0003b.g)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$l */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$l.class */
    public static class l implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$m */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$m.class */
    public static class m implements InterfaceC0004c {
        private static final String[] a = {"no", "yes", "auto"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            c0003b.a(b, c);
            if (S.a(a, c0003b.g)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$n */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$n.class */
    public static class n implements InterfaceC0004c {
        private static final String[] a = {"rect", "default", "circle", "poly"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            c0003b.a(b, c);
            if (S.a(a, c0003b.g)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$o */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$o.class */
    public static class o implements InterfaceC0004c {
        private static final String[] a = {"_blank", "_self", "_parent", "_top"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            b.c(-5);
            if (c0003b.g == null || c0003b.g.length() == 0) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            String str = c0003b.g;
            if (Character.isLetter(str.charAt(0)) || S.a(a, str)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$p */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$p.class */
    public static class p implements InterfaceC0004c {
        private static final String[] a = {"rtl", "ltr"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            c0003b.a(b, c);
            if (S.a(a, c0003b.g)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$q */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$q.class */
    public static class q implements InterfaceC0004c {
        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            boolean z = false;
            boolean z2 = false;
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            String str = c0003b.g;
            for (int i = 0; i < str.length(); i++) {
                char cCharAt = str.charAt(i);
                if (cCharAt == '\\') {
                    z2 = true;
                } else if (cCharAt > '~' || cCharAt <= ' ' || cCharAt == '<' || cCharAt == '>') {
                    z = true;
                }
            }
            if (b.z.Q && z2) {
                c0003b.g = c0003b.g.replace('\\', '/');
                str = c0003b.g;
            }
            if (b.z.ab && z) {
                StringBuffer stringBuffer = new StringBuffer();
                for (int i2 = 0; i2 < str.length(); i2++) {
                    char cCharAt2 = str.charAt(i2);
                    if (cCharAt2 > '~' || cCharAt2 <= ' ' || cCharAt2 == '<' || cCharAt2 == '>') {
                        stringBuffer.append('%');
                        stringBuffer.append(Integer.toHexString(cCharAt2).toUpperCase());
                    } else {
                        stringBuffer.append(cCharAt2);
                    }
                }
                c0003b.g = stringBuffer.toString();
            }
            if (z2) {
                if (b.z.Q) {
                    b.C.a(b, c, c0003b, (short) 62);
                } else {
                    b.C.a(b, c, c0003b, (short) 61);
                }
            }
            if (z) {
                if (b.z.ab) {
                    b.C.a(b, c, c0003b, (short) 64);
                } else {
                    b.C.a(b, c, c0003b, (short) 63);
                }
                b.e = (short) (b.e | 81);
            }
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$r */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$r.class */
    public static class r implements InterfaceC0004c {
        private static final String[] a = {"data", "object", "ref"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b, C c, C0003b c0003b) {
            if (c0003b.g == null) {
                b.C.a(b, c, c0003b, (short) 50);
                return;
            }
            c0003b.a(b, c);
            if (S.a(a, c0003b.g)) {
                return;
            }
            b.C.a(b, c, c0003b, (short) 51);
        }
    }

    /* JADX INFO: renamed from: org.w3c.tidy.d$s */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/d$s.class */
    public static class s implements InterfaceC0004c {
        private static final String[] a = {"top", "middle", "bottom", "baseline"};
        private static final String[] b = {"left", "right"};
        private static final String[] c = {"texttop", "absmiddle", "absbottom", "textbottom"};

        @Override // org.w3c.tidy.InterfaceC0004c
        public final void a(B b2, C c2, C0003b c0003b) {
            if (c0003b.g == null) {
                b2.C.a(b2, c2, c0003b, (short) 50);
                return;
            }
            c0003b.a(b2, c2);
            String str = c0003b.g;
            if (S.a(a, str)) {
                return;
            }
            if (S.a(b, str)) {
                if (c2.m == null || (c2.m.c & 65536) == 0) {
                    b2.C.a(b2, c2, c0003b, (short) 51);
                    return;
                }
                return;
            }
            if (!S.a(c, str)) {
                b2.C.a(b2, c2, c0003b, (short) 51);
            } else {
                b2.c(448);
                b2.C.a(b2, c2, c0003b, (short) 54);
            }
        }
    }
}
