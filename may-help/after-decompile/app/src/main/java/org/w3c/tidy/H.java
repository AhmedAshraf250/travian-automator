package org.w3c.tidy;

import java.util.Iterator;
import java.util.List;
import java.util.StringTokenizer;
import traviaut.xml.TAData;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H.class */
public final class H {
    static final G a = new f();
    static final G b = new a();
    static final G c = new g();
    static final G d = new c();
    static final G e = new h();
    static final G f = new l();
    static final G g = new d();
    static final G h = new j();
    static final G i = new k();
    static final G j = new e();
    static final G k = new b();
    static final G l = new i();

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$a.class */
    static class a implements G {
        a() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            Boolean bool = Boolean.TRUE;
            if (str != null && str.length() > 0) {
                char cCharAt = str.charAt(0);
                if (cCharAt == 't' || cCharAt == 'T' || cCharAt == 'Y' || cCharAt == 'y' || cCharAt == '1') {
                    bool = Boolean.TRUE;
                } else if (cCharAt == 'f' || cCharAt == 'F' || cCharAt == 'N' || cCharAt == 'n' || cCharAt == '0') {
                    bool = Boolean.FALSE;
                } else {
                    K k = c0009h.aq;
                    K.a(str, str2);
                }
            }
            return bool;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Boolean";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "y/n, yes/no, t/f, true/false, 1/0";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return obj == null ? "" : ((Boolean) obj).booleanValue() ? "yes" : "no";
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$b.class */
    static class b implements G {
        b() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            StringTokenizer stringTokenizer = new StringTokenizer(str);
            String str3 = null;
            if (stringTokenizer.countTokens() > 0) {
                str3 = stringTokenizer.nextToken() + "-";
            } else {
                K k = c0009h.aq;
                K.a(str, str2);
            }
            if (!B.c(str)) {
                K k2 = c0009h.aq;
                K.a(str, str2);
            }
            return str3;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Name";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "CSS1 selector";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return obj == null ? "" : (String) obj;
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$c.class */
    static class c implements G {
        c() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            if ("raw".equalsIgnoreCase(str)) {
                c0009h.at = true;
                return null;
            }
            if (!S.c(str)) {
                K k = c0009h.aq;
                K.a(str, str2);
                return null;
            }
            if ("input-encoding".equalsIgnoreCase(str2)) {
                c0009h.c(str);
                return null;
            }
            if ("output-encoding".equalsIgnoreCase(str2)) {
                c0009h.d(str);
                return null;
            }
            if (!"char-encoding".equalsIgnoreCase(str2)) {
                return null;
            }
            c0009h.c(str);
            c0009h.d(str);
            return null;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Encoding";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "Any valid java char encoding name";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return "output-encoding".equalsIgnoreCase(str) ? c0009h.c() : c0009h.b();
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$d.class */
    static class d implements G {
        d() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            String strTrim = str.trim();
            if (strTrim.startsWith("\"")) {
                c0009h.d = 4;
                return strTrim;
            }
            StringTokenizer stringTokenizer = new StringTokenizer(strTrim, " \t\n\r,");
            String strNextToken = stringTokenizer.hasMoreTokens() ? stringTokenizer.nextToken() : "";
            if ("auto".equalsIgnoreCase(strNextToken)) {
                c0009h.d = 1;
                return null;
            }
            if ("omit".equalsIgnoreCase(strNextToken)) {
                c0009h.d = 0;
                return null;
            }
            if ("strict".equalsIgnoreCase(strNextToken)) {
                c0009h.d = 2;
                return null;
            }
            if ("loose".equalsIgnoreCase(strNextToken) || "transitional".equalsIgnoreCase(strNextToken)) {
                c0009h.d = 3;
                return null;
            }
            K k = c0009h.aq;
            K.a(strTrim, str2);
            return null;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "DocType";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "omit | auto | strict | loose | [fpi]";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            String str2;
            switch (c0009h.d) {
                case 0:
                    str2 = "omit";
                    break;
                case 1:
                    str2 = "auto";
                    break;
                case 2:
                    str2 = "strict";
                    break;
                case 3:
                    str2 = "transitional";
                    break;
                case TAData.ACT_VER /* 4 */:
                    str2 = c0009h.g;
                    break;
                default:
                    str2 = "unknown";
                    break;
            }
            return str2;
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$e.class */
    static class e implements G {
        e() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            boolean z = c0009h.m;
            if ("yes".equalsIgnoreCase(str) || "true".equalsIgnoreCase(str)) {
                z = true;
                c0009h.n = false;
            } else if ("no".equalsIgnoreCase(str) || "false".equalsIgnoreCase(str)) {
                z = false;
                c0009h.n = false;
            } else if ("auto".equalsIgnoreCase(str)) {
                z = true;
                c0009h.n = true;
            } else {
                K k = c0009h.aq;
                K.a(str, str2);
            }
            return z ? Boolean.TRUE : Boolean.FALSE;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Indent";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "auto, y/n, yes/no, t/f, true/false, 1/0";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return obj == null ? "" : obj.toString();
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$f.class */
    static class f implements G {
        f() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            int i;
            try {
                i = Integer.parseInt(str);
            } catch (NumberFormatException unused) {
                K k = c0009h.aq;
                K.a(str, str2);
                i = -1;
            }
            return new Integer(i);
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Integer";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "0, 1, 2, ...";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return obj == null ? "" : obj.toString();
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$g.class */
    static class g implements G {
        g() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            return ((Boolean) H.b.a(str, str2, c0009h)).booleanValue() ? Boolean.FALSE : Boolean.TRUE;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Boolean";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "yes, no, true, false";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return obj == null ? "" : ((Boolean) obj).booleanValue() ? "no" : "yes";
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$h.class */
    static class h implements G {
        h() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            StringTokenizer stringTokenizer = new StringTokenizer(str);
            String strNextToken = null;
            if (stringTokenizer.countTokens() > 0) {
                strNextToken = stringTokenizer.nextToken();
            } else {
                K k = c0009h.aq;
                K.a(str, str2);
            }
            return strNextToken;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Name";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "-";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return obj == null ? "" : obj.toString();
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$i.class */
    static class i implements G {
        i() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            if ("lf".equalsIgnoreCase(str)) {
                c0009h.as = new char[]{'\n'};
                return null;
            }
            if ("cr".equalsIgnoreCase(str)) {
                c0009h.as = new char[]{'\r'};
                return null;
            }
            if ("crlf".equalsIgnoreCase(str)) {
                c0009h.as = new char[]{'\r', '\n'};
                return null;
            }
            K k = c0009h.aq;
            K.a(str, str2);
            return null;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Enum";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "lf, crlf, cr";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return c0009h.as.length == 1 ? c0009h.as[0] == '\n' ? "lf" : "cr" : "crlf";
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$j.class */
    static class j implements G {
        j() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            int i;
            if ("keep-first".equalsIgnoreCase(str)) {
                i = 1;
            } else if ("keep-last".equalsIgnoreCase(str)) {
                i = 0;
            } else {
                K k = c0009h.aq;
                K.a(str, str2);
                i = -1;
            }
            return new Integer(i);
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Enum";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "keep-first, keep-last";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            String str2;
            if (obj == null) {
                return "";
            }
            switch (((Integer) obj).intValue()) {
                case 0:
                    str2 = "keep-last";
                    break;
                case 1:
                    str2 = "keep-first";
                    break;
                default:
                    str2 = "unknown";
                    break;
            }
            return str2;
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$k.class */
    static class k implements G {
        k() {
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            return str;
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "String";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "-";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            return obj == null ? "" : (String) obj;
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/H$l.class */
    static class l implements G {
        l() {
        }

        @Override // org.w3c.tidy.G
        public final String a() {
            return "Tag names";
        }

        @Override // org.w3c.tidy.G
        public final String b() {
            return "tagX, tagY, ...";
        }

        @Override // org.w3c.tidy.G
        public final String a(String str, Object obj, C0009h c0009h) {
            short s;
            if ("new-inline-tags".equals(str)) {
                s = 2;
            } else if ("new-blocklevel-tags".equals(str)) {
                s = 4;
            } else if ("new-empty-tags".equals(str)) {
                s = 1;
            } else {
                if (!"new-pre-tags".equals(str)) {
                    return "";
                }
                s = 8;
            }
            List listA = c0009h.ap.a(s);
            if (listA.isEmpty()) {
                return "";
            }
            StringBuffer stringBuffer = new StringBuffer();
            Iterator it = listA.iterator();
            while (it.hasNext()) {
                stringBuffer.append(it.next());
                stringBuffer.append(" ");
            }
            return stringBuffer.toString();
        }

        @Override // org.w3c.tidy.G
        public final Object a(String str, String str2, C0009h c0009h) {
            int i;
            I i2;
            int i3 = 2;
            if ("new-inline-tags".equals(str2)) {
                i3 = 2;
            } else if ("new-blocklevel-tags".equals(str2)) {
                i3 = 4;
            } else if ("new-empty-tags".equals(str2)) {
                i3 = 1;
            } else if ("new-pre-tags".equals(str2)) {
                i3 = 8;
            }
            StringTokenizer stringTokenizer = new StringTokenizer(str, " \t\n\r,");
            while (stringTokenizer.hasMoreTokens()) {
                c0009h.ar |= i3;
                Q q = c0009h.ap;
                int i4 = i3;
                String strNextToken = stringTokenizer.nextToken();
                switch (i4) {
                    case 1:
                        i = 1;
                        i2 = J.k;
                        break;
                    case 2:
                    case 3:
                    case 5:
                    case 6:
                    case 7:
                    default:
                        i = 16;
                        i2 = J.g;
                        break;
                    case TAData.ACT_VER /* 4 */:
                        i = 8;
                        i2 = J.k;
                        break;
                    case 8:
                        i = 8;
                        i2 = J.j;
                        break;
                }
                q.a(new v(strNextToken, (short) 448, i, i2, null));
            }
            return null;
        }
    }
}
