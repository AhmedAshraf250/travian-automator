package org.w3c.tidy;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/* JADX INFO: renamed from: org.w3c.tidy.s, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/s.class */
public final class C0020s implements NodeList {
    private C a;

    protected C0020s(C c) {
        this.a = c;
    }

    @Override // org.w3c.dom.NodeList
    public final Node item(int i) {
        C c;
        if (this.a == null) {
            return null;
        }
        int i2 = 0;
        C c2 = this.a.p;
        while (true) {
            c = c2;
            if (c == null || i2 >= i) {
                break;
            }
            i2++;
            c2 = c.c;
        }
        if (c != null) {
            return c.g();
        }
        return null;
    }

    @Override // org.w3c.dom.NodeList
    public final int getLength() {
        if (this.a == null) {
            return 0;
        }
        int i = 0;
        C c = this.a.p;
        while (true) {
            C c2 = c;
            if (c2 == null) {
                return i;
            }
            i++;
            c = c2.c;
        }
    }
}
