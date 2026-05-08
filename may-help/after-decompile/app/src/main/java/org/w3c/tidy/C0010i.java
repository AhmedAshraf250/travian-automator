package org.w3c.tidy;

import org.w3c.dom.Attr;
import org.w3c.dom.DOMException;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.w3c.dom.TypeInfo;

/* JADX INFO: renamed from: org.w3c.tidy.i, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/i.class */
public final class C0010i extends C0018q implements Cloneable, Attr {
    protected C0003b a;

    protected C0010i(C0003b c0003b) {
        super(null);
        this.a = c0003b;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final String getNodeValue() throws DOMException {
        return getValue();
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final void setNodeValue(String str) throws DOMException {
        setValue(str);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final String getNodeName() {
        return getName();
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final short getNodeType() {
        return (short) 2;
    }

    @Override // org.w3c.dom.Attr
    public final String getName() {
        return this.a.f;
    }

    @Override // org.w3c.dom.Attr
    public final boolean getSpecified() {
        return this.a.g != null;
    }

    @Override // org.w3c.dom.Attr
    public final String getValue() {
        return this.a.g == null ? this.a.f : this.a.g;
    }

    @Override // org.w3c.dom.Attr
    public final void setValue(String str) {
        this.a.g = str;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node getParentNode() {
        return null;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final NodeList getChildNodes() {
        return new C0020s(null);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node getFirstChild() {
        return null;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node getLastChild() {
        return null;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node getPreviousSibling() {
        return null;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node getNextSibling() {
        return null;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final NamedNodeMap getAttributes() {
        return null;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Document getOwnerDocument() {
        return null;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node insertBefore(Node node, Node node2) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node replaceChild(Node node, Node node2) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node removeChild(Node node) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node appendChild(Node node) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final boolean hasChildNodes() {
        return false;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final Node cloneNode(boolean z) {
        return (Node) clone();
    }

    @Override // org.w3c.dom.Attr
    public final Element getOwnerElement() {
        return null;
    }

    @Override // org.w3c.dom.Attr
    public final TypeInfo getSchemaTypeInfo() {
        return null;
    }

    @Override // org.w3c.dom.Attr
    public final boolean isId() {
        return "id".equals(this.a.c());
    }

    protected final Object clone() {
        try {
            C0010i c0010i = (C0010i) super.clone();
            c0010i.a = (C0003b) this.a.clone();
            return c0010i;
        } catch (CloneNotSupportedException unused) {
            throw new RuntimeException("Clone not supported");
        }
    }
}
