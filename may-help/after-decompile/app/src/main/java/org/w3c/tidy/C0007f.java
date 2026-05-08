package org.w3c.tidy;

import java.util.Hashtable;
import java.util.Map;

/* JADX INFO: renamed from: org.w3c.tidy.f, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/f.class */
public final class C0007f {
    protected static C0006e a;
    protected static C0006e b;
    protected static C0006e c;
    protected static C0006e d;
    protected static C0006e e;
    protected static C0006e f;
    protected static C0006e g;
    private static C0006e k;
    private static C0006e l;
    protected static C0006e h;
    protected static C0006e i;
    protected static C0006e j;
    private static C0007f m;
    private static final C0006e[] n = {new C0006e("abbr", 28, C0005d.t), new C0006e("accept-charset", 28, C0005d.u), new C0006e("accept", 3103, C0005d.v), new C0006e("accesskey", 28, C0005d.w), new C0006e("action", 3103, C0005d.a), new C0006e("add_date", 64, C0005d.t), new C0006e("align", 3103, C0005d.e), new C0006e("alink", 26, C0005d.o), new C0006e("alt", 3103, C0005d.t), new C0006e("archive", 28, C0005d.x), new C0006e("axis", 28, C0005d.t), new C0006e("background", 26, C0005d.a), new C0006e("bgcolor", 26, C0005d.o), new C0006e("bgproperties", 448, C0005d.t), new C0006e("border", 3103, C0005d.g), new C0006e("bordercolor", 128, C0005d.o), new C0006e("bottommargin", 128, C0005d.m), new C0006e("cellpadding", 30, C0005d.h), new C0006e("cellspacing", 30, C0005d.h), new C0006e("char", 28, C0005d.w), new C0006e("charoff", 28, C0005d.h), new C0006e("charset", 28, C0005d.u), new C0006e("checked", 3103, C0005d.g), new C0006e("cite", 28, C0005d.a), new C0006e("class", 28, C0005d.t), new C0006e("classid", 28, C0005d.a), new C0006e("clear", 26, C0005d.k), new C0006e("code", 26, C0005d.t), new C0006e("codebase", 28, C0005d.a), new C0006e("codetype", 28, C0005d.v), new C0006e("color", 26, C0005d.o), new C0006e("cols", 24, C0005d.y), new C0006e("colspan", 30, C0005d.m), new C0006e("compact", 3103, C0005d.g), new C0006e("content", 3103, C0005d.t), new C0006e("coords", 30, C0005d.z), new C0006e("data", 28, C0005d.a), new C0006e("datafld", 128, C0005d.t), new C0006e("dataformatas", 128, C0005d.t), new C0006e("datapagesize", 128, C0005d.m), new C0006e("datasrc", 128, C0005d.a), new C0006e("datetime", 28, C0005d.A), new C0006e("declare", 28, C0005d.g), new C0006e("defer", 28, C0005d.g), new C0006e("dir", 28, C0005d.r), new C0006e("disabled", 28, C0005d.g), new C0006e("enctype", 3103, C0005d.v), new C0006e("face", 26, C0005d.t), new C0006e("for", 28, C0005d.B), new C0006e("frame", 28, C0005d.C), new C0006e("frameborder", 24, C0005d.D), new C0006e("framespacing", 448, C0005d.m), new C0006e("gridx", 448, C0005d.m), new C0006e("gridy", 448, C0005d.m), new C0006e("headers", 28, C0005d.B), new C0006e("height", 3103, C0005d.h), new C0006e("href", 3103, C0005d.a), new C0006e("hreflang", 28, C0005d.s), new C0006e("hspace", 3103, C0005d.m), new C0006e("http-equiv", 3103, C0005d.t), new C0006e("id", 28, C0005d.d), new C0006e("ismap", 3103, C0005d.g), new C0006e("label", 28, C0005d.t), new C0006e("lang", 28, C0005d.s), new C0006e("language", 26, C0005d.t), new C0006e("last_modified", 64, C0005d.t), new C0006e("last_visit", 64, C0005d.t), new C0006e("leftmargin", 128, C0005d.m), new C0006e("link", 26, C0005d.o), new C0006e("longdesc", 28, C0005d.a), new C0006e("lowsrc", 448, C0005d.a), new C0006e("marginheight", 24, C0005d.m), new C0006e("marginwidth", 24, C0005d.m), new C0006e("maxlength", 3103, C0005d.m), new C0006e("media", 28, C0005d.E), new C0006e("method", 3103, C0005d.j), new C0006e("multiple", 3103, C0005d.g), new C0006e("name", 3103, C0005d.c), new C0006e("nohref", 30, C0005d.g), new C0006e("noresize", 16, C0005d.g), new C0006e("noshade", 26, C0005d.g), new C0006e("nowrap", 26, C0005d.g), new C0006e("object", 8, C0005d.t), new C0006e("onblur", 1052, C0005d.b), new C0006e("onchange", 1052, C0005d.b), new C0006e("onclick", 1052, C0005d.b), new C0006e("ondblclick", 1052, C0005d.b), new C0006e("onkeydown", 1052, C0005d.b), new C0006e("onkeypress", 1052, C0005d.b), new C0006e("onkeyup", 1052, C0005d.b), new C0006e("onload", 1052, C0005d.b), new C0006e("onmousedown", 1052, C0005d.b), new C0006e("onmousemove", 1052, C0005d.b), new C0006e("onmouseout", 1052, C0005d.b), new C0006e("onmouseover", 1052, C0005d.b), new C0006e("onmouseup", 1052, C0005d.b), new C0006e("onsubmit", 1052, C0005d.b), new C0006e("onreset", 1052, C0005d.b), new C0006e("onselect", 1052, C0005d.b), new C0006e("onunload", 1052, C0005d.b), new C0006e("onfocus", 1052, C0005d.b), new C0006e("onafterupdate", 128, C0005d.b), new C0006e("onbeforeupdate", 128, C0005d.b), new C0006e("onerrorupdate", 128, C0005d.b), new C0006e("onrowenter", 128, C0005d.b), new C0006e("onrowexit", 128, C0005d.b), new C0006e("onbeforeunload", 128, C0005d.b), new C0006e("ondatasetchanged", 128, C0005d.b), new C0006e("ondataavailable", 128, C0005d.b), new C0006e("ondatasetcomplete", 128, C0005d.b), new C0006e("profile", 28, C0005d.a), new C0006e("prompt", 26, C0005d.t), new C0006e("readonly", 28, C0005d.g), new C0006e("rel", 3103, C0005d.F), new C0006e("rev", 3103, C0005d.F), new C0006e("rightmargin", 128, C0005d.m), new C0006e("rows", 3103, C0005d.m), new C0006e("rowspan", 3103, C0005d.m), new C0006e("rules", 28, C0005d.G), new C0006e("scheme", 28, C0005d.t), new C0006e("scope", 28, C0005d.n), new C0006e("scrolling", 24, C0005d.q), new C0006e("selected", 3103, C0005d.g), new C0006e("shape", 30, C0005d.l), new C0006e("showgrid", 448, C0005d.g), new C0006e("showgridx", 448, C0005d.g), new C0006e("showgridy", 448, C0005d.g), new C0006e("size", 26, C0005d.m), new C0006e("span", 28, C0005d.m), new C0006e("src", 3103, C0005d.a), new C0006e("standby", 28, C0005d.t), new C0006e("start", 3103, C0005d.m), new C0006e("style", 28, C0005d.t), new C0006e("summary", 28, C0005d.t), new C0006e("tabindex", 28, C0005d.m), new C0006e("target", 28, C0005d.i), new C0006e("text", 26, C0005d.o), new C0006e("title", 28, C0005d.t), new C0006e("topmargin", 128, C0005d.m), new C0006e("type", 30, C0005d.v), new C0006e("usemap", 3103, C0005d.g), new C0006e("valign", 30, C0005d.f), new C0006e("value", 3103, C0005d.t), new C0006e("valuetype", 28, C0005d.p), new C0006e("version", 3103, C0005d.t), new C0006e("vlink", 26, C0005d.o), new C0006e("vspace", 26, C0005d.m), new C0006e("width", 3103, C0005d.h), new C0006e("wrap", 64, C0005d.t), new C0006e("xml:lang", 32, C0005d.t), new C0006e("xml:space", 32, C0005d.t), new C0006e("xmlns", 3103, C0005d.t), new C0006e("rbspan", 1024, C0005d.m)};
    private final Map<String, C0006e> o = new Hashtable();

    private C0006e c(String str) {
        return this.o.get(str);
    }

    public final C0006e a(C0003b c0003b) {
        if (c0003b.f != null) {
            return c(c0003b.f);
        }
        return null;
    }

    public final boolean a(String str) {
        C0006e c0006eC = c(str);
        return c0006eC != null && c0006eC.a() == C0005d.a;
    }

    public final boolean b(String str) {
        C0006e c0006eC = c(str);
        return c0006eC != null && c0006eC.a() == C0005d.b;
    }

    public static C0007f a() {
        if (m == null) {
            m = new C0007f();
            for (int i2 = 0; i2 < 153; i2++) {
                C0007f c0007f = m;
                C0006e c0006e = n[i2];
                c0007f.o.put(c0006e.b(), c0006e);
            }
            a = m.c("href");
            b = m.c("src");
            m.c("id");
            m.c("name");
            c = m.c("summary");
            d = m.c("alt");
            m.c("longdesc");
            e = m.c("usemap");
            f = m.c("ismap");
            m.c("language");
            m.c("type");
            g = m.c("title");
            m.c("xmlns");
            k = m.c("value");
            l = m.c("content");
            h = m.c("datafld");
            i = m.c("width");
            j = m.c("height");
            d.a(true);
            k.a(true);
            l.a(true);
        }
        return m;
    }
}
