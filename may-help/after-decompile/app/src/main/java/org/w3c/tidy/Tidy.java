package org.w3c.tidy;

import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.PrintWriter;
import java.io.Serializable;
import java.util.HashMap;
import java.util.Map;
import java.util.Properties;
import org.w3c.dom.Document;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/Tidy.class */
public class Tidy implements Serializable {
    private static final Map<String, String> a;
    private PrintWriter b;
    private PrintWriter c;
    private int f;
    private int g;
    private String e = "InputStream";
    private K h = new K();
    private C0009h d = new C0009h(this.h);

    public Tidy() {
        Q q = new Q();
        q.a(this.d);
        this.d.ap = q;
        this.d.h = null;
        this.c = new PrintWriter((OutputStream) System.err, true);
        this.b = this.c;
    }

    public final void a(Properties properties) {
        this.d.a(properties);
    }

    private C a(L l, D d) {
        C c;
        if (this.b == null) {
            return null;
        }
        this.d.a();
        this.f = 0;
        this.g = 0;
        B b = new B(l, this.d, this.h);
        b.b = this.b;
        this.h.b(this.e);
        if (!this.d.l) {
            this.h.d(this.b);
        }
        if (this.d.p) {
            C cB = J.b(b);
            c = cB;
            if (!cB.f()) {
                if (this.d.l) {
                    return null;
                }
                this.h.f(this.b);
                return null;
            }
        } else {
            b.g = (short) 0;
            C cA = J.a(b);
            c = cA;
            if (!cA.f()) {
                if (this.d.l) {
                    return null;
                }
                this.h.f(this.b);
                return null;
            }
            C0008g c0008g = new C0008g(this.d.ap);
            c0008g.a(c);
            c0008g.b(c);
            c0008g.c(c);
            boolean z = this.d.y;
            boolean z2 = this.d.W;
            if (this.d.w) {
                c0008g.a(b, c);
            } else {
                boolean z3 = this.d.z;
            }
            if (!c.f()) {
                this.h.f(this.b);
                return null;
            }
            C cA2 = c.a();
            C c2 = cA2;
            if (cA2 != null) {
                c2 = (C) c2.clone();
            }
            if (c.p != null) {
                if (this.d.r) {
                    b.d(c);
                } else {
                    b.e(c);
                }
                if (this.d.X) {
                    b.b(c);
                }
            }
            if (this.d.q && this.d.t) {
                b.f(c);
            }
            if (!this.d.l && c.p != null) {
                this.h.a(b, this.e, c2);
            }
        }
        if (!this.d.l) {
            this.g = b.g;
            this.f = b.h;
            this.h.a(this.b, b);
        }
        if (!this.d.l && b.h > 0) {
            boolean z4 = this.d.ag;
            this.h.b(this.b);
        }
        if (!this.d.j) {
            if (b.h != 0) {
                boolean z5 = this.d.ag;
            } else {
                boolean z6 = this.d.F;
                if (d != null) {
                    F f = new F(this.d);
                    if (c.a() == null) {
                        this.d.G = true;
                    }
                    boolean z7 = this.d.aa;
                    if (!this.d.q || this.d.r) {
                        f.a(d, (short) 0, 0, b, c);
                    } else {
                        f.b(d, (short) 0, 0, b, c);
                    }
                    f.a(d, 0);
                    d.b();
                }
            }
        }
        if (!this.d.l) {
            this.h.a(b);
        }
        return c;
    }

    private C a(InputStream inputStream, String str, OutputStream outputStream) throws IOException {
        InputStream fileInputStream;
        D dA = null;
        boolean z = false;
        boolean z2 = false;
        if (str != null) {
            fileInputStream = new FileInputStream(str);
            z = true;
            this.e = str;
        } else {
            fileInputStream = System.in;
            this.e = "stdin";
        }
        L lA = C0002a.a(this.d, fileInputStream);
        if (this.d.i && str != null) {
            outputStream = new FileOutputStream(str);
            z2 = true;
        }
        if (outputStream != null) {
            dA = C0002a.a(this.d, outputStream);
        }
        C cA = a(lA, dA);
        if (z) {
            try {
                fileInputStream.close();
            } catch (IOException unused) {
            }
        }
        if (z2) {
            try {
                outputStream.close();
            } catch (IOException unused2) {
            }
        }
        return cA;
    }

    public static void main(String[] strArr) {
        System.exit(new Tidy().a(strArr));
    }

    /* JADX WARN: Removed duplicated region for block: B:91:0x02ce  */
    /* JADX WARN: Removed duplicated region for block: B:94:0x02dc A[RETURN] */
    /* JADX WARN: Removed duplicated region for block: B:96:0x02de  */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    private int a(java.lang.String[] r7) {
        /*
            Method dump skipped, instruction units count: 745
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.Tidy.a(java.lang.String[]):int");
    }

    public final void a(boolean z) {
        this.d.j = true;
    }

    public final void b(boolean z) {
        this.d.k = false;
    }

    public final void c(boolean z) {
        this.d.l = true;
    }

    static {
        HashMap map = new HashMap();
        a = map;
        map.put("xml", "input-xml");
        a.put("xml", "output-xhtml");
        a.put("asxml", "output-xhtml");
        a.put("ashtml", "output-html");
        a.put("omit", "hide-endtags");
        a.put("upper", "uppercase-tags");
        a.put("raw", "output-raw");
        a.put("numeric", "numeric-entities");
        a.put("change", "write-back");
        a.put("update", "write-back");
        a.put("modify", "write-back");
        a.put("errors", "only-errors");
        a.put("slides", "split");
        a.put("lang", "language");
        a.put("w", "wrap");
        a.put("file", "error-file");
        a.put("f", "error-file");
    }

    public final Document a(InputStream inputStream, OutputStream outputStream) {
        L lA = C0002a.a(this.d, inputStream);
        D dA = null;
        if (outputStream != null) {
            dA = C0002a.a(this.d, outputStream);
        }
        C cA = a(lA, dA);
        if (cA != null) {
            return (Document) cA.g();
        }
        return null;
    }
}
