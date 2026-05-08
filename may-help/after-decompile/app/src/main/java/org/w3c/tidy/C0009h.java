package org.w3c.tidy;

import java.io.FileInputStream;
import java.io.IOException;
import java.io.Serializable;
import java.io.Writer;
import java.lang.reflect.Field;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.Map;
import java.util.Properties;

/* JADX INFO: renamed from: org.w3c.tidy.h, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/h.class */
public class C0009h implements Serializable {
    private static final Map<String, a> au = new HashMap();
    protected String f;
    protected String g;
    protected String h;
    protected boolean i;
    protected boolean j;
    protected boolean l;
    protected boolean m;
    protected boolean n;
    protected boolean o;
    protected boolean p;
    protected boolean q;
    protected boolean r;
    protected boolean s;
    protected boolean t;
    protected boolean u;
    protected boolean v;
    protected boolean w;
    protected boolean x;
    protected boolean y;
    protected boolean z;
    protected boolean A;
    protected boolean E;
    protected boolean F;
    protected boolean G;
    protected boolean H;
    protected boolean K;
    protected boolean L;
    protected boolean R;
    protected boolean S;
    protected boolean T;
    protected boolean U;
    protected boolean V;
    protected boolean W;
    protected boolean Y;
    protected boolean Z;
    protected boolean aa;
    protected boolean ad;
    protected boolean ae;
    protected boolean af;
    protected boolean ag;
    protected boolean aj;
    protected String an;
    public Q ap;
    protected K aq;
    protected int ar;
    protected boolean at;
    protected int a = 2;
    public int b = 68;
    protected int c = 8;
    protected int d = 1;
    protected int e = 0;
    protected boolean k = true;
    protected boolean B = true;
    protected boolean C = true;
    protected boolean D = true;
    protected boolean I = true;
    protected boolean J = true;
    protected boolean M = true;
    protected boolean N = true;
    protected boolean O = true;
    protected boolean P = true;
    protected boolean Q = true;
    protected boolean X = true;
    protected boolean ab = true;
    protected boolean ac = true;
    protected int ah = 6;
    protected boolean ai = true;
    protected boolean ak = true;
    protected boolean al = true;
    protected boolean am = true;
    protected String ao = "WIN1252";
    public char[] as = System.getProperty("line.separator").toCharArray();
    private String av = "ISO8859_1";
    private String aw = "ASCII";
    private transient Properties ax = new Properties();

    public C0009h(K k) {
        String[] strArr = {"raw", "ASCII", "ISO8859_1", "UTF8", "JIS", "MacRoman", "UnicodeLittle", "UnicodeBig", "Unicode", "Cp1252", "Big5", "SJIS"};
        this.aq = k;
    }

    private static void a(a aVar) {
        au.put(aVar.b(), aVar);
    }

    public final void a(Properties properties) {
        Enumeration<?> enumerationPropertyNames = properties.propertyNames();
        while (enumerationPropertyNames.hasMoreElements()) {
            String str = (String) enumerationPropertyNames.nextElement();
            this.ax.put(str, properties.getProperty(str));
        }
        d();
    }

    public final void a(String str) {
        try {
            this.ax.load(new FileInputStream(str));
            d();
        } catch (IOException e) {
            System.err.println(str + " " + e.toString());
        }
    }

    public static boolean b(String str) {
        return str != null && au.containsKey(str);
    }

    private void d() {
        for (String str : this.ax.keySet()) {
            a aVar = au.get(str);
            if (aVar == null) {
                K.a(str);
            } else {
                Object objA = aVar.c().a(this.ax.getProperty(str), str, this);
                if (aVar.a() != null) {
                    try {
                        aVar.a().set(this, objA);
                    } catch (IllegalAccessException e) {
                        throw new RuntimeException("IllegalArgumentException during config initialization for field " + str + "with value [" + objA + "]: " + e.getMessage());
                    } catch (IllegalArgumentException e2) {
                        throw new RuntimeException("IllegalArgumentException during config initialization for field " + str + "with value [" + objA + "]: " + e2.getMessage());
                    }
                } else {
                    continue;
                }
            }
        }
    }

    final void a(Writer writer, boolean z) {
        try {
            writer.write("\nConfiguration File Settings:\n\n");
            if (z) {
                writer.write("Name                        Type       Current Value\n");
            } else {
                writer.write("Name                        Type       Allowable values\n");
            }
            writer.write("=========================== =========  ========================================\n");
            ArrayList<a> arrayList = new ArrayList(au.values());
            Collections.sort(arrayList);
            for (a aVar : arrayList) {
                writer.write(aVar.b());
                writer.write("                                                                               ", 0, 28 - aVar.b().length());
                writer.write(aVar.c().a());
                writer.write("                                                                               ", 0, 11 - aVar.c().a().length());
                if (z) {
                    Field fieldA = aVar.a();
                    Object obj = null;
                    if (fieldA != null) {
                        try {
                            obj = fieldA.get(this);
                        } catch (IllegalAccessException unused) {
                            throw new RuntimeException("IllegalAccess when reading field " + fieldA.getName());
                        } catch (IllegalArgumentException unused2) {
                            throw new RuntimeException("IllegalArgument when reading field " + fieldA.getName());
                        }
                    }
                    writer.write(aVar.c().a(aVar.b(), obj, this));
                } else {
                    writer.write(aVar.c().b());
                }
                writer.write("\n");
            }
            writer.flush();
        } catch (IOException e) {
            throw new RuntimeException(e.getMessage());
        }
    }

    protected final String b() {
        return this.av;
    }

    protected final void c(String str) {
        String strA = w.a(str);
        if (strA != null) {
            this.av = strA;
        }
    }

    protected final String c() {
        return this.aw;
    }

    protected final void d(String str) {
        String strA = w.a(str);
        if (strA != null) {
            this.aw = strA;
        }
    }

    static {
        a(new a("indent-spaces", "spaces", H.a));
        a(new a("wrap", "wraplen", H.a));
        a(new a("show-errors", "showErrors", H.a));
        a(new a("tab-size", "tabsize", H.a));
        a(new a("wrap-attributes", "wrapAttVals", H.b));
        a(new a("wrap-script-literals", "wrapScriptlets", H.b));
        a(new a("wrap-sections", "wrapSection", H.b));
        a(new a("wrap-asp", "wrapAsp", H.b));
        a(new a("wrap-jste", "wrapJste", H.b));
        a(new a("wrap-php", "wrapPhp", H.b));
        a(new a("literal-attributes", "literalAttribs", H.b));
        a(new a("show-body-only", "bodyOnly", H.b));
        a(new a("fix-uri", "fixUri", H.b));
        a(new a("lower-literals", "lowerLiterals", H.b));
        a(new a("hide-comments", "hideComments", H.b));
        a(new a("indent-cdata", "indentCdata", H.b));
        a(new a("force-output", "forceOutput", H.b));
        a(new a("ascii-chars", "asciiChars", H.b));
        a(new a("join-classes", "joinClasses", H.b));
        a(new a("join-styles", "joinStyles", H.b));
        a(new a("escape-cdata", "escapeCdata", H.b));
        a(new a("replace-color", "replaceColor", H.b));
        a(new a("quiet", "quiet", H.b));
        a(new a("tidy-mark", "tidyMark", H.b));
        a(new a("indent-attributes", "indentAttributes", H.b));
        a(new a("hide-endtags", "hideEndTags", H.b));
        a(new a("input-xml", "xmlTags", H.b));
        a(new a("output-xml", "xmlOut", H.b));
        a(new a("output-html", "htmlOut", H.b));
        a(new a("output-xhtml", "xHTML", H.b));
        a(new a("add-xml-pi", "xmlPi", H.b));
        a(new a("add-xml-decl", "xmlPi", H.b));
        a(new a("assume-xml-procins", "xmlPIs", H.b));
        a(new a("uppercase-tags", "upperCaseTags", H.b));
        a(new a("uppercase-attributes", "upperCaseAttrs", H.b));
        a(new a("bare", "makeBare", H.b));
        a(new a("clean", "makeClean", H.b));
        a(new a("logical-emphasis", "logicalEmphasis", H.b));
        a(new a("word-2000", "word2000", H.b));
        a(new a("drop-empty-paras", "dropEmptyParas", H.b));
        a(new a("drop-font-tags", "dropFontTags", H.b));
        a(new a("drop-proprietary-attributes", "dropProprietaryAttributes", H.b));
        a(new a("enclose-text", "encloseBodyText", H.b));
        a(new a("enclose-block-text", "encloseBlockText", H.b));
        a(new a("add-xml-space", "xmlSpace", H.b));
        a(new a("fix-bad-comments", "fixComments", H.b));
        a(new a("split", "burstSlides", H.b));
        a(new a("break-before-br", "breakBeforeBR", H.b));
        a(new a("numeric-entities", "numEntities", H.b));
        a(new a("quote-marks", "quoteMarks", H.b));
        a(new a("quote-nbsp", "quoteNbsp", H.b));
        a(new a("quote-ampersand", "quoteAmpersand", H.b));
        a(new a("write-back", "writeback", H.b));
        a(new a("keep-time", "keepFileTimes", H.b));
        a(new a("show-warnings", "showWarnings", H.b));
        a(new a("ncr", "ncr", H.b));
        a(new a("fix-backslash", "fixBackslash", H.b));
        a(new a("gnu-emacs", "emacs", H.b));
        a(new a("only-errors", "onlyErrors", H.b));
        a(new a("output-raw", "rawOut", H.b));
        a(new a("trim-empty-elements", "trimEmpty", H.b));
        a(new a("markup", "onlyErrors", H.c));
        a(new a("char-encoding", null, H.d));
        a(new a("input-encoding", null, H.d));
        a(new a("output-encoding", null, H.d));
        a(new a("error-file", "errfile", H.e));
        a(new a("slide-style", "slidestyle", H.e));
        a(new a("language", "language", H.e));
        a(new a("new-inline-tags", null, H.f));
        a(new a("new-blocklevel-tags", null, H.f));
        a(new a("new-empty-tags", null, H.f));
        a(new a("new-pre-tags", null, H.f));
        a(new a("doctype", "docTypeStr", H.g));
        a(new a("repeated-attributes", "duplicateAttrs", H.h));
        a(new a("alt-text", "altText", H.i));
        a(new a("indent", "indentContent", H.j));
        a(new a("css-prefix", "cssPrefix", H.k));
        a(new a("newline", null, H.l));
    }

    /* JADX INFO: renamed from: org.w3c.tidy.h$a */
    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/h$a.class */
    static class a implements Comparable<a> {
        private String a;
        private String b;
        private Field c;
        private G d;

        a(String str, String str2, G g) {
            this.b = str2;
            this.a = str;
            this.d = g;
        }

        public final Field a() {
            if (this.b != null && this.c == null) {
                try {
                    this.c = C0009h.class.getDeclaredField(this.b);
                } catch (NoSuchFieldException unused) {
                    throw new RuntimeException("NoSuchField exception during config initialization for field " + this.b);
                } catch (SecurityException e) {
                    throw new RuntimeException("Security exception during config initialization for field " + this.b + ": " + e.getMessage());
                }
            }
            return this.c;
        }

        public final String b() {
            return this.a;
        }

        public final G c() {
            return this.d;
        }

        public final boolean equals(Object obj) {
            return this.a.equals(((a) obj).a);
        }

        public final int hashCode() {
            return this.a.hashCode();
        }

        @Override // java.lang.Comparable
        public final /* bridge */ /* synthetic */ int compareTo(a aVar) {
            return this.a.compareTo(aVar.a);
        }
    }

    public final void a() {
        if (this.n) {
            this.m = true;
        }
        if (this.b == 0) {
            this.b = Integer.MAX_VALUE;
        }
        if (this.p) {
            this.r = false;
        }
        if (this.r) {
            this.q = true;
            this.u = false;
            this.v = false;
        }
        if (this.p) {
            this.q = true;
            this.S = true;
        }
        if (!"UTF8".equals(this.aw) && !"ASCII".equals(this.aw) && this.q) {
            this.t = true;
        }
        if (this.q) {
            this.J = true;
            this.o = false;
        }
    }
}
