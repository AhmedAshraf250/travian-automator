package org.w3c.tidy;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/* JADX INFO: renamed from: org.w3c.tidy.r, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/r.class */
public final class C0019r implements NodeList {
    private C a;
    private String b;
    private int c;
    private int d;
    private C e;

    protected C0019r(C c, String str) {
        this.a = c;
        this.b = str;
    }

    @Override // org.w3c.dom.NodeList
    public final Node item(int i) {
        this.c = 0;
        this.d = i;
        a(this.a);
        if (this.c <= this.d || this.e == null) {
            return null;
        }
        return this.e.g();
    }

    @Override // org.w3c.dom.NodeList
    public final int getLength() {
        this.c = 0;
        this.d = Integer.MAX_VALUE;
        a(this.a);
        return this.c;
    }

    private void a(C c) {
        if (c == null) {
            return;
        }
        if ((c.h == 5 || c.h == 7) && this.c <= this.d && (this.b.equals("*") || this.b.equals(c.n))) {
            this.c++;
            this.e = c;
        }
        if (this.c > this.d) {
            return;
        }
        C c2 = c.p;
        while (true) {
            C c3 = c2;
            if (c3 == null) {
                return;
            }
            a(c3);
            c2 = c3.c;
        }
    }
}
