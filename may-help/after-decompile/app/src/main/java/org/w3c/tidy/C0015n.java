package org.w3c.tidy;

import org.w3c.dom.Attr;
import org.w3c.dom.CDATASection;
import org.w3c.dom.Comment;
import org.w3c.dom.DOMConfiguration;
import org.w3c.dom.DOMException;
import org.w3c.dom.DOMImplementation;
import org.w3c.dom.Document;
import org.w3c.dom.DocumentFragment;
import org.w3c.dom.DocumentType;
import org.w3c.dom.Element;
import org.w3c.dom.EntityReference;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.w3c.dom.ProcessingInstruction;
import org.w3c.dom.Text;

/* JADX INFO: renamed from: org.w3c.tidy.n, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/n.class */
public final class C0015n extends C0018q implements Document {
    private Q a;

    protected C0015n(C c) {
        super(c);
        this.a = new Q();
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final String getNodeName() {
        return "#document";
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final short getNodeType() {
        return (short) 9;
    }

    @Override // org.w3c.dom.Document
    public final DocumentType getDoctype() {
        C c;
        C c2 = this.b.p;
        while (true) {
            c = c2;
            if (c == null || c.h == 1) {
                break;
            }
            c2 = c.c;
        }
        if (c != null) {
            return (DocumentType) c.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Document
    public final DOMImplementation getImplementation() {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Document
    public final Element getDocumentElement() {
        C c;
        C c2 = this.b.p;
        while (true) {
            c = c2;
            if (c == null || c.h == 5 || c.h == 7) {
                break;
            }
            c2 = c.c;
        }
        if (c != null) {
            return (Element) c.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Document
    public final Element createElement(String str) throws DOMException {
        C c = new C((short) 7, null, 0, 0, str, this.a);
        if (c.m == null) {
            c.m = Q.a;
        }
        return (Element) c.g();
    }

    @Override // org.w3c.dom.Document
    public final DocumentFragment createDocumentFragment() {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Document
    public final Text createTextNode(String str) {
        byte[] bArrA = S.a(str);
        return (Text) new C((short) 4, bArrA, 0, bArrA.length).g();
    }

    @Override // org.w3c.dom.Document
    public final Comment createComment(String str) {
        byte[] bArrA = S.a(str);
        return (Comment) new C((short) 2, bArrA, 0, bArrA.length).g();
    }

    @Override // org.w3c.dom.Document
    public final CDATASection createCDATASection(String str) throws DOMException {
        throw new DOMException((short) 9, "HTML document");
    }

    @Override // org.w3c.dom.Document
    public final ProcessingInstruction createProcessingInstruction(String str, String str2) throws DOMException {
        throw new DOMException((short) 9, "HTML document");
    }

    @Override // org.w3c.dom.Document
    public final Attr createAttribute(String str) throws DOMException {
        C0003b c0003b = new C0003b(null, null, 34, str, null);
        c0003b.b = C0007f.a().a(c0003b);
        return c0003b.b();
    }

    @Override // org.w3c.dom.Document
    public final EntityReference createEntityReference(String str) throws DOMException {
        throw new DOMException((short) 9, "createEntityReference not supported");
    }

    @Override // org.w3c.dom.Document
    public final NodeList getElementsByTagName(String str) {
        return new C0019r(this.b, str);
    }

    @Override // org.w3c.dom.Document
    public final Node importNode(Node node, boolean z) throws DOMException {
        throw new DOMException((short) 9, "importNode not supported");
    }

    @Override // org.w3c.dom.Document
    public final Attr createAttributeNS(String str, String str2) throws DOMException {
        throw new DOMException((short) 9, "createAttributeNS not supported");
    }

    @Override // org.w3c.dom.Document
    public final Element createElementNS(String str, String str2) throws DOMException {
        throw new DOMException((short) 9, "createElementNS not supported");
    }

    @Override // org.w3c.dom.Document
    public final NodeList getElementsByTagNameNS(String str, String str2) {
        throw new DOMException((short) 9, "getElementsByTagNameNS not supported");
    }

    @Override // org.w3c.dom.Document
    public final Element getElementById(String str) {
        return null;
    }

    @Override // org.w3c.dom.Document
    public final Node adoptNode(Node node) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Document
    public final String getDocumentURI() {
        return null;
    }

    @Override // org.w3c.dom.Document
    public final DOMConfiguration getDomConfig() {
        return null;
    }

    @Override // org.w3c.dom.Document
    public final String getInputEncoding() {
        return null;
    }

    @Override // org.w3c.dom.Document
    public final boolean getStrictErrorChecking() {
        return true;
    }

    @Override // org.w3c.dom.Document
    public final String getXmlEncoding() {
        return null;
    }

    @Override // org.w3c.dom.Document
    public final boolean getXmlStandalone() {
        return false;
    }

    @Override // org.w3c.dom.Document
    public final String getXmlVersion() {
        return "1.0";
    }

    @Override // org.w3c.dom.Document
    public final void normalizeDocument() {
    }

    @Override // org.w3c.dom.Document
    public final Node renameNode(Node node, String str, String str2) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Document
    public final void setDocumentURI(String str) {
    }

    @Override // org.w3c.dom.Document
    public final void setStrictErrorChecking(boolean z) {
    }

    @Override // org.w3c.dom.Document
    public final void setXmlStandalone(boolean z) throws DOMException {
    }

    @Override // org.w3c.dom.Document
    public final void setXmlVersion(String str) throws DOMException {
    }
}
