package org.w3c.tidy;

import java.util.ArrayList;
import java.util.Hashtable;
import java.util.List;
import java.util.Map;
import traviaut.xml.TAData;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/Q.class */
public final class Q {
    public static final v a = new v(null, 3103, 8, null, null);
    private static final v[] al = {new v("html", 3103, 2129922, J.a, P.a), new v("head", 3103, 2129922, J.b, null), new v("title", 3103, 4, J.c, null), new v("base", 3103, 5, J.s, null), new v("link", 3103, 5, J.s, P.k), new v("meta", 3103, 5, J.s, P.n), new v("style", 28, 4, J.d, P.i), new v("script", 28, 131100, J.d, P.b), new v("server", 64, 131100, J.d, null), new v("body", 3103, 2129922, J.e, null), new v("frameset", 16, 8194, J.f, null), new v("p", 3103, 32776, J.g, null), new v("h1", 3103, 16392, J.k, null), new v("h2", 3103, 16392, J.k, null), new v("h3", 3103, 16392, J.k, null), new v("h4", 3103, 16392, J.k, null), new v("h5", 3103, 16392, J.k, null), new v("h6", 3103, 16392, J.k, null), new v("ul", 3103, 8, J.h, null), new v("ol", 3103, 8, J.h, null), new v("dl", 3103, 8, J.i, null), new v("dir", 26, 524296, J.h, null), new v("menu", 26, 524296, J.h, null), new v("pre", 3103, 8, J.j, null), new v("listing", 3103, 524296, J.j, null), new v("xmp", 3103, 524296, J.j, null), new v("plaintext", 3103, 524296, J.j, null), new v("address", 3103, 8, J.k, null), new v("blockquote", 3103, 8, J.k, null), new v("form", 3103, 8, J.k, P.m), new v("isindex", 26, 9, J.s, null), new v("fieldset", 28, 8, J.k, null), new v("table", 30, 8, J.l, P.c), new v("hr", 1055, 9, J.s, P.l), new v("div", 30, 8, J.k, null), new v("multicol", 64, 8, J.k, null), new v("nosave", 64, 8, J.k, null), new v("layer", 64, 8, J.k, null), new v("ilayer", 64, 16, J.g, null), new v("nolayer", 64, 131096, J.k, null), new v("align", 64, 8, J.k, null), new v("center", 26, 8, J.k, null), new v("ins", 28, 131096, J.g, null), new v("del", 28, 131096, J.g, null), new v("li", 3103, 294944, J.k, null), new v("dt", 3103, 294976, J.g, null), new v("dd", 3103, 294976, J.k, null), new v("caption", 30, 128, J.g, P.d), new v("colgroup", 28, 32896, J.m, null), new v("col", 28, 129, J.s, null), new v("thead", 28, 33152, J.n, null), new v("tfoot", 28, 33152, J.n, null), new v("tbody", 28, 33152, J.n, null), new v("tr", 30, 32896, J.o, null), new v("td", 30, 295424, J.k, P.j), new v("th", 30, 295424, J.k, P.j), new v("q", 28, 16, J.g, null), new v("a", 3103, 16, J.g, P.g), new v("br", 3103, 17, J.s, null), new v("img", 3103, 65553, J.s, P.e), new v("object", 28, 71700, J.k, null), new v("applet", 26, 71696, J.k, null), new v("servlet", 256, 71696, J.k, null), new v("param", 30, 17, J.s, null), new v("embed", 64, 65553, J.s, null), new v("noembed", 64, 16, J.g, null), new v("iframe", 8, 16, J.k, null), new v("frame", 16, 8193, J.s, null), new v("noframes", 24, 8200, J.p, null), new v("noscript", 28, 131096, J.k, null), new v("b", 1055, 16, J.g, null), new v("i", 1055, 16, J.g, null), new v("u", 26, 16, J.g, null), new v("tt", 1055, 16, J.g, null), new v("s", 26, 16, J.g, null), new v("strike", 26, 16, J.g, null), new v("big", 28, 16, J.g, null), new v("small", 28, 16, J.g, null), new v("sub", 28, 16, J.g, null), new v("sup", 28, 16, J.g, null), new v("em", 3103, 16, J.g, null), new v("strong", 3103, 16, J.g, null), new v("dfn", 3103, 16, J.g, null), new v("code", 3103, 16, J.g, null), new v("samp", 3103, 16, J.g, null), new v("kbd", 3103, 16, J.g, null), new v("var", 3103, 16, J.g, null), new v("cite", 3103, 16, J.g, null), new v("abbr", 28, 16, J.g, null), new v("acronym", 28, 16, J.g, null), new v("span", 30, 16, J.g, null), new v("blink", 448, 16, J.g, null), new v("nobr", 448, 16, J.g, null), new v("wbr", 448, 17, J.s, null), new v("marquee", 128, 32784, J.g, null), new v("bgsound", 128, 5, J.s, null), new v("comment", 128, 16, J.g, null), new v("spacer", 64, 17, J.s, null), new v("keygen", 64, 17, J.s, null), new v("nolayer", 64, 131096, J.k, null), new v("ilayer", 64, 16, J.g, null), new v("map", 28, 16, J.k, P.h), new v("area", 1055, 9, J.s, P.f), new v("input", 3103, 65553, J.s, null), new v("select", 3103, 1040, J.q, null), new v("option", 3103, 33792, J.r, null), new v("optgroup", 28, 33792, J.t, null), new v("textarea", 3103, 1040, J.r, null), new v("label", 28, 16, J.g, null), new v("legend", 28, 16, J.g, null), new v("button", 28, 16, J.g, null), new v("basefont", 26, 17, J.s, null), new v("font", 26, 16, J.g, null), new v("bdo", 28, 16, J.g, null), new v("ruby", 1024, 16, J.g, null), new v("rbc", 1024, 16, J.g, null), new v("rtc", 1024, 16, J.g, null), new v("rb", 1024, 16, J.g, null), new v("rt", 1024, 16, J.g, null), new v("", 1024, 16, J.g, null), new v("rp", 1024, 16, J.g, null)};
    protected v b;
    protected v c;
    protected v d;
    protected v e;
    protected v f;
    protected v g;
    protected v h;
    protected v i;
    protected v j;
    protected v k;
    protected v l;
    protected v m;
    protected v n;
    protected v o;
    protected v p;
    protected v q;
    protected v r;
    protected v s;
    protected v t;
    protected v u;
    protected v v;
    protected v w;
    protected v x;
    protected v y;
    protected v z;
    protected v A;
    protected v B;
    protected v C;
    protected v D;
    protected v E;
    protected v F;
    protected v G;
    protected v H;
    protected v I;
    protected v J;
    protected v K;
    protected v L;
    protected v M;
    protected v N;
    protected v O;
    protected v P;
    protected v Q;
    protected v R;
    protected v S;
    protected v T;
    protected v U;
    protected v V;
    protected v W;
    protected v X;
    protected v Y;
    protected v Z;
    protected v aa;
    protected v ab;
    protected v ac;
    protected v ad;
    protected v ae;
    protected v af;
    protected v ag;
    protected v ah;
    protected v ai;
    protected v aj;
    private v am;
    protected C0002a ak;
    private C0009h an;
    private Map<String, v> ao = new Hashtable();

    public Q() {
        for (int i = 0; i < 121; i++) {
            a(al[i]);
        }
        this.b = b("html");
        this.c = b("head");
        this.d = b("body");
        this.e = b("frameset");
        this.f = b("frame");
        this.g = b("iframe");
        this.h = b("noframes");
        this.i = b("meta");
        this.j = b("title");
        this.k = b("base");
        this.l = b("hr");
        this.m = b("pre");
        b("listing");
        b("h1");
        this.n = b("h2");
        this.o = b("p");
        this.p = b("ul");
        this.q = b("ol");
        this.r = b("dir");
        this.s = b("li");
        this.t = b("dt");
        this.u = b("dd");
        this.v = b("dl");
        this.w = b("td");
        this.x = b("th");
        this.y = b("tr");
        this.z = b("col");
        this.A = b("colgroup");
        this.B = b("br");
        this.C = b("a");
        this.D = b("link");
        this.E = b("b");
        this.F = b("i");
        this.G = b("strong");
        this.H = b("em");
        this.I = b("big");
        this.J = b("small");
        this.K = b("param");
        this.L = b("option");
        this.M = b("optgroup");
        this.N = b("img");
        this.O = b("map");
        this.P = b("area");
        this.Q = b("nobr");
        this.R = b("wbr");
        this.S = b("font");
        this.T = b("spacer");
        this.U = b("layer");
        this.V = b("center");
        this.W = b("style");
        this.X = b("script");
        this.Y = b("noscript");
        this.Z = b("table");
        this.aa = b("caption");
        this.ab = b("form");
        this.ac = b("textarea");
        this.ad = b("blockquote");
        this.ae = b("applet");
        this.af = b("object");
        this.ag = b("div");
        this.ah = b("span");
        this.ai = b("input");
        this.aj = b("q");
        this.am = b("blink");
    }

    public final void a(C0009h c0009h) {
        this.an = c0009h;
    }

    private v b(String str) {
        return this.ao.get(str);
    }

    public final v a(v vVar) {
        v vVar2 = this.ao.get(vVar.a);
        if (vVar2 == null) {
            this.ao.put(vVar.a, vVar);
            return vVar;
        }
        vVar2.b = vVar.b;
        vVar2.c |= vVar.c;
        vVar2.a(vVar.b());
        vVar2.a(vVar.a());
        return vVar2;
    }

    public final boolean a(C c) {
        v vVarB;
        if (this.an != null && this.an.p) {
            c.m = a;
            return true;
        }
        if (c.n == null || (vVarB = b(c.n)) == null) {
            return false;
        }
        c.m = vVarB;
        return true;
    }

    public final I b(C c) {
        v vVarB;
        if (c.n == null || (vVarB = b(c.n)) == null) {
            return null;
        }
        return vVarB.b();
    }

    final boolean c(C c) {
        return c.m == this.C || c.m == this.ae || c.m == this.ab || c.m == this.f || c.m == this.g || c.m == this.N || c.m == this.O;
    }

    final List a(short s) {
        ArrayList arrayList = new ArrayList();
        for (v vVar : this.ao.values()) {
            if (vVar != null) {
                switch (s) {
                    case 1:
                        if (vVar.b == 448 && (vVar.c & 1) == 1 && vVar != this.R) {
                            arrayList.add(vVar.a);
                        }
                        break;
                    case 2:
                        if (vVar.b == 448 && (vVar.c & 16) == 16 && vVar != this.am && vVar != this.Q && vVar != this.R) {
                            arrayList.add(vVar.a);
                        }
                        break;
                    case TAData.ACT_VER /* 4 */:
                        if (vVar.b == 448 && (vVar.c & 8) == 8 && vVar.b() == J.k) {
                            arrayList.add(vVar.a);
                        }
                        break;
                    case 8:
                        if (vVar.b == 448 && (vVar.c & 8) == 8 && vVar.b() == J.j) {
                            arrayList.add(vVar.a);
                        }
                        break;
                }
            }
        }
        return arrayList;
    }

    final C a(String str) {
        C0002a c0002a;
        C0002a c0002a2 = this.ak;
        while (true) {
            c0002a = c0002a2;
            if (c0002a == null || str.equalsIgnoreCase(c0002a.a)) {
                break;
            }
            c0002a2 = c0002a.b;
        }
        if (c0002a != null) {
            return c0002a.c;
        }
        return null;
    }

    final C0002a a(String str, C c) {
        C0002a c0002a;
        C0002a c0002a2 = new C0002a();
        c0002a2.a = str;
        c0002a2.c = c;
        if (this.ak == null) {
            this.ak = c0002a2;
        } else {
            C0002a c0002a3 = this.ak;
            while (true) {
                c0002a = c0002a3;
                if (c0002a.b == null) {
                    break;
                }
                c0002a3 = c0002a.b;
            }
            c0002a.b = c0002a2;
        }
        return this.ak;
    }
}
