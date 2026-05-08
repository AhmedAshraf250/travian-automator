package org.w3c.tidy;

import org.w3c.dom.Attr;
import org.w3c.dom.DOMException;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.w3c.dom.TypeInfo;

/* JADX INFO: renamed from: org.w3c.tidy.p, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/p.class */
public final class C0017p extends C0018q implements Element {
    protected C0017p(C c) {
        super(c);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final short getNodeType() {
        return (short) 1;
    }

    @Override // org.w3c.dom.Element
    public final String getTagName() {
        return super.getNodeName();
    }

    @Override // org.w3c.dom.Element
    public final String getAttribute(String str) {
        C0003b c0003b;
        if (this.b == null) {
            return null;
        }
        C0003b c0003b2 = this.b.o;
        while (true) {
            c0003b = c0003b2;
            if (c0003b == null || c0003b.f.equals(str)) {
                break;
            }
            c0003b2 = c0003b.a;
        }
        return c0003b != null ? c0003b.g : "";
    }

    @Override // org.w3c.dom.Element
    public final void setAttribute(String str, String str2) throws DOMException {
        C0003b c0003b;
        if (this.b == null) {
            return;
        }
        C0003b c0003b2 = this.b.o;
        while (true) {
            c0003b = c0003b2;
            if (c0003b == null || c0003b.f.equals(str)) {
                break;
            } else {
                c0003b2 = c0003b.a;
            }
        }
        if (c0003b != null) {
            c0003b.g = str2;
            return;
        }
        C0003b c0003b3 = new C0003b(null, null, 34, str, str2);
        c0003b3.b = C0007f.a().a(c0003b3);
        if (this.b.o == null) {
            this.b.o = c0003b3;
        } else {
            c0003b3.a = this.b.o;
            this.b.o = c0003b3;
        }
    }

    @Override // org.w3c.dom.Element
    public final void removeAttribute(String str) throws DOMException {
        if (this.b == null) {
            return;
        }
        C0003b c0003b = this.b.o;
        C0003b c0003b2 = null;
        while (c0003b != null && !c0003b.f.equals(str)) {
            c0003b2 = c0003b;
            c0003b = c0003b.a;
        }
        if (c0003b != null) {
            if (c0003b2 == null) {
                this.b.o = c0003b.a;
            } else {
                c0003b2.a = c0003b.a;
            }
        }
    }

    @Override // org.w3c.dom.Element
    public final Attr getAttributeNode(String str) {
        C0003b c0003b;
        if (this.b == null) {
            return null;
        }
        C0003b c0003b2 = this.b.o;
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

    @Override // org.w3c.dom.Element
    public final Attr setAttributeNode(Attr attr) throws DOMException {
        C0003b c0003b;
        if (attr == null) {
            return null;
        }
        if (!(attr instanceof C0010i)) {
            throw new DOMException((short) 4, "newAttr not instanceof DOMAttrImpl");
        }
        C0010i c0010i = (C0010i) attr;
        String str = c0010i.a.f;
        Attr attrB = null;
        C0003b c0003b2 = this.b.o;
        while (true) {
            c0003b = c0003b2;
            if (c0003b == null || c0003b.f.equals(str)) {
                break;
            }
            c0003b2 = c0003b.a;
        }
        if (c0003b != null) {
            attrB = c0003b.b();
            c0003b.h = attr;
        } else if (this.b.o == null) {
            this.b.o = c0010i.a;
        } else {
            c0010i.a.a = this.b.o;
            this.b.o = c0010i.a;
        }
        return attrB;
    }

    @Override // org.w3c.dom.Element
    public final Attr removeAttributeNode(Attr attr) throws DOMException {
        if (attr == null) {
            return null;
        }
        C0003b c0003b = this.b.o;
        C0003b c0003b2 = null;
        while (c0003b != null && c0003b.b() != attr) {
            c0003b2 = c0003b;
            c0003b = c0003b.a;
        }
        if (c0003b == null) {
            throw new DOMException((short) 8, "oldAttr not found");
        }
        if (c0003b2 == null) {
            this.b.o = c0003b.a;
        } else {
            c0003b2.a = c0003b.a;
        }
        return attr;
    }

    @Override // org.w3c.dom.Element
    public final NodeList getElementsByTagName(String str) {
        return new C0019r(this.b, str);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final void normalize() {
    }

    @Override // org.w3c.dom.Element
    public final String getAttributeNS(String str, String str2) {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final void setAttributeNS(String str, String str2, String str3) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final void removeAttributeNS(String str, String str2) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final Attr getAttributeNodeNS(String str, String str2) {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final Attr setAttributeNodeNS(Attr attr) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final NodeList getElementsByTagNameNS(String str, String str2) {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final boolean hasAttribute(String str) {
        return false;
    }

    @Override // org.w3c.dom.Element
    public final boolean hasAttributeNS(String str, String str2) {
        return false;
    }

    @Override // org.w3c.dom.Element
    public final TypeInfo getSchemaTypeInfo() {
        return null;
    }

    @Override // org.w3c.dom.Element
    public final void setIdAttribute(String str, boolean z) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final void setIdAttributeNode(Attr attr, boolean z) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Element
    public final void setIdAttributeNS(String str, String str2, boolean z) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }
}
