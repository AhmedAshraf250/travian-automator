package org.w3c.tidy;

import org.w3c.dom.DOMException;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;

/* JADX INFO: renamed from: org.w3c.tidy.j, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/j.class */
public final class C0011j implements NamedNodeMap {
    private C0003b a;

    protected C0011j(C0003b c0003b) {
        this.a = c0003b;
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final Node getNamedItem(String str) {
        C0003b c0003b;
        C0003b c0003b2 = this.a;
        while (true) {
            c0003b = c0003b2;
            if (c0003b == null || c0003b.f.equals(str)) {
                break;
            }
            c0003b2 = c0003b.a;
        }
        if (c0003b != null) {
            return c0003b.b();
        }
        return null;
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final Node item(int i) {
        C0003b c0003b;
        int i2 = 0;
        C0003b c0003b2 = this.a;
        while (true) {
            c0003b = c0003b2;
            if (c0003b == null || i2 >= i) {
                break;
            }
            i2++;
            c0003b2 = c0003b.a;
        }
        if (c0003b != null) {
            return c0003b.b();
        }
        return null;
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final int getLength() {
        int i = 0;
        C0003b c0003b = this.a;
        while (true) {
            C0003b c0003b2 = c0003b;
            if (c0003b2 == null) {
                return i;
            }
            i++;
            c0003b = c0003b2.a;
        }
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final Node setNamedItem(Node node) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final Node removeNamedItem(String str) throws DOMException {
        C0003b c0003b = this.a;
        C0003b c0003b2 = null;
        while (true) {
            if (c0003b == null) {
                break;
            }
            if (!c0003b.f.equals(str)) {
                c0003b2 = c0003b;
                c0003b = c0003b.a;
            } else if (c0003b2 == null) {
                this.a = c0003b.d();
            } else {
                c0003b2.a(c0003b.d());
            }
        }
        if (c0003b != null) {
            return c0003b.b();
        }
        throw new DOMException((short) 8, "Named item " + str + "Not found");
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final Node getNamedItemNS(String str, String str2) {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final Node setNamedItemNS(Node node) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.NamedNodeMap
    public final Node removeNamedItemNS(String str, String str2) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }
}
