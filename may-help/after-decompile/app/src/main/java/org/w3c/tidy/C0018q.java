package org.w3c.tidy;

import org.w3c.dom.DOMException;
import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.w3c.dom.UserDataHandler;
import traviaut.xml.TAData;

/* JADX INFO: renamed from: org.w3c.tidy.q, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/q.class */
public class C0018q implements Node {
    public C b;

    protected C0018q(C c) {
        this.b = c;
    }

    @Override // org.w3c.dom.Node
    public String getNodeValue() {
        String strA = "";
        if ((this.b.h == 4 || this.b.h == 8 || this.b.h == 2 || this.b.h == 3) && this.b.g != null && this.b.e < this.b.f) {
            strA = S.a(this.b.g, this.b.e, this.b.f - this.b.e);
        }
        return strA;
    }

    @Override // org.w3c.dom.Node
    public void setNodeValue(String str) {
        if (this.b.h == 4 || this.b.h == 8 || this.b.h == 2 || this.b.h == 3) {
            byte[] bArrA = S.a(str);
            this.b.g = bArrA;
            this.b.e = 0;
            this.b.f = bArrA.length;
        }
    }

    @Override // org.w3c.dom.Node
    public String getNodeName() {
        return this.b.n;
    }

    @Override // org.w3c.dom.Node
    public short getNodeType() {
        short s = -1;
        switch (this.b.h) {
            case 0:
                s = 9;
                break;
            case 1:
                s = 10;
                break;
            case 2:
                s = 8;
                break;
            case 3:
                s = 7;
                break;
            case TAData.ACT_VER /* 4 */:
                s = 3;
                break;
            case 5:
            case 7:
                s = 1;
                break;
            case 8:
                s = 4;
                break;
        }
        return s;
    }

    @Override // org.w3c.dom.Node
    public Node getParentNode() {
        if (this.b.a != null) {
            return this.b.a.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Node
    public NodeList getChildNodes() {
        return new C0020s(this.b);
    }

    @Override // org.w3c.dom.Node
    public Node getFirstChild() {
        if (this.b.p != null) {
            return this.b.p.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Node
    public Node getLastChild() {
        if (this.b.d != null) {
            return this.b.d.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Node
    public Node getPreviousSibling() {
        if (this.b.b != null) {
            return this.b.b.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Node
    public Node getNextSibling() {
        if (this.b.c != null) {
            return this.b.c.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Node
    public NamedNodeMap getAttributes() {
        return new C0011j(this.b.o);
    }

    @Override // org.w3c.dom.Node
    public Document getOwnerDocument() {
        C c = this.b;
        C c2 = c;
        if (c != null && c2.h == 0) {
            return null;
        }
        while (c2 != null && c2.h != 0) {
            c2 = c2.a;
        }
        if (c2 != null) {
            return (Document) c2.g();
        }
        return null;
    }

    @Override // org.w3c.dom.Node
    public Node insertBefore(Node node, Node node2) {
        C c;
        if (node == null) {
            return null;
        }
        if (!(node instanceof C0018q)) {
            throw new DOMException((short) 4, "newChild not instanceof DOMNodeImpl");
        }
        C0018q c0018q = (C0018q) node;
        if (this.b.h == 0) {
            if (c0018q.b.h != 1 && c0018q.b.h != 3) {
                throw new DOMException((short) 3, "newChild cannot be a child of this node");
            }
        } else if (this.b.h == 5 && c0018q.b.h != 5 && c0018q.b.h != 7 && c0018q.b.h != 2 && c0018q.b.h != 4 && c0018q.b.h != 8) {
            throw new DOMException((short) 3, "newChild cannot be a child of this node");
        }
        if (node2 == null) {
            this.b.c(c0018q.b);
            if (this.b.h == 7) {
                this.b.a((short) 5);
            }
        } else {
            C c2 = this.b.p;
            while (true) {
                c = c2;
                if (c == null || c.g() == node2) {
                    break;
                }
                c2 = c.c;
            }
            if (c == null) {
                throw new DOMException((short) 8, "refChild not found");
            }
            C.b(c, c0018q.b);
        }
        return node;
    }

    @Override // org.w3c.dom.Node
    public Node replaceChild(Node node, Node node2) {
        C c;
        if (node == null) {
            return null;
        }
        if (!(node instanceof C0018q)) {
            throw new DOMException((short) 4, "newChild not instanceof DOMNodeImpl");
        }
        C0018q c0018q = (C0018q) node;
        if (this.b.h == 0) {
            if (c0018q.b.h != 1 && c0018q.b.h != 3) {
                throw new DOMException((short) 3, "newChild cannot be a child of this node");
            }
        } else if (this.b.h == 5 && c0018q.b.h != 5 && c0018q.b.h != 7 && c0018q.b.h != 2 && c0018q.b.h != 4 && c0018q.b.h != 8) {
            throw new DOMException((short) 3, "newChild cannot be a child of this node");
        }
        if (node2 == null) {
            throw new DOMException((short) 8, "oldChild not found");
        }
        C c2 = this.b.p;
        while (true) {
            c = c2;
            if (c == null || c.g() == node2) {
                break;
            }
            c2 = c.c;
        }
        if (c == null) {
            throw new DOMException((short) 8, "oldChild not found");
        }
        c0018q.b.c = c.c;
        c0018q.b.b = c.b;
        c0018q.b.d = c.d;
        c0018q.b.a = c.a;
        c0018q.b.p = c.p;
        if (c.a != null) {
            if (c.a.p == c) {
                c.a.p = c0018q.b;
            }
            if (c.a.d == c) {
                c.a.d = c0018q.b;
            }
        }
        if (c.b != null) {
            c.b.c = c0018q.b;
        }
        if (c.c != null) {
            c.c.b = c0018q.b;
        }
        C c3 = c.p;
        while (true) {
            C c4 = c3;
            if (c4 == null) {
                return node2;
            }
            if (c4.a == c) {
                c4.a = c0018q.b;
            }
            c3 = c4.c;
        }
    }

    @Override // org.w3c.dom.Node
    public Node removeChild(Node node) {
        C c;
        if (node == null) {
            return null;
        }
        C c2 = this.b.p;
        while (true) {
            c = c2;
            if (c == null || c.g() == node) {
                break;
            }
            c2 = c.c;
        }
        if (c == null) {
            throw new DOMException((short) 8, "refChild not found");
        }
        C.a(c);
        if (this.b.p == null && this.b.h == 5) {
            this.b.a((short) 7);
        }
        return node;
    }

    @Override // org.w3c.dom.Node
    public Node appendChild(Node node) {
        if (node == null) {
            return null;
        }
        if (!(node instanceof C0018q)) {
            throw new DOMException((short) 4, "newChild not instanceof DOMNodeImpl");
        }
        C0018q c0018q = (C0018q) node;
        if (this.b.h == 0) {
            if (c0018q.b.h != 1 && c0018q.b.h != 3) {
                throw new DOMException((short) 3, "newChild cannot be a child of this node");
            }
        } else if (this.b.h == 5 && c0018q.b.h != 5 && c0018q.b.h != 7 && c0018q.b.h != 2 && c0018q.b.h != 4 && c0018q.b.h != 8) {
            throw new DOMException((short) 3, "newChild cannot be a child of this node");
        }
        this.b.c(c0018q.b);
        if (this.b.h == 7) {
            this.b.a((short) 5);
        }
        return node;
    }

    @Override // org.w3c.dom.Node
    public boolean hasChildNodes() {
        return this.b.p != null;
    }

    @Override // org.w3c.dom.Node
    public Node cloneNode(boolean z) {
        C cA = this.b.a(z);
        cA.a = null;
        return cA.g();
    }

    @Override // org.w3c.dom.Node
    public void normalize() {
    }

    @Override // org.w3c.dom.Node
    public String getNamespaceURI() {
        return null;
    }

    @Override // org.w3c.dom.Node
    public String getPrefix() {
        return null;
    }

    @Override // org.w3c.dom.Node
    public void setPrefix(String str) throws DOMException {
    }

    @Override // org.w3c.dom.Node
    public String getLocalName() {
        return getNodeName();
    }

    @Override // org.w3c.dom.Node
    public boolean isSupported(String str, String str2) {
        return false;
    }

    @Override // org.w3c.dom.Node
    public boolean hasAttributes() {
        return this.b.o != null;
    }

    @Override // org.w3c.dom.Node
    public short compareDocumentPosition(Node node) throws DOMException {
        throw new DOMException((short) 9, "DOM method not supported");
    }

    @Override // org.w3c.dom.Node
    public String getBaseURI() {
        return null;
    }

    @Override // org.w3c.dom.Node
    public Object getFeature(String str, String str2) {
        return null;
    }

    @Override // org.w3c.dom.Node
    public String getTextContent() throws DOMException {
        return null;
    }

    @Override // org.w3c.dom.Node
    public Object getUserData(String str) {
        return null;
    }

    @Override // org.w3c.dom.Node
    public boolean isDefaultNamespace(String str) {
        return false;
    }

    @Override // org.w3c.dom.Node
    public boolean isEqualNode(Node node) {
        return false;
    }

    @Override // org.w3c.dom.Node
    public boolean isSameNode(Node node) {
        return false;
    }

    @Override // org.w3c.dom.Node
    public String lookupNamespaceURI(String str) {
        return null;
    }

    @Override // org.w3c.dom.Node
    public String lookupPrefix(String str) {
        return null;
    }

    @Override // org.w3c.dom.Node
    public void setTextContent(String str) throws DOMException {
        throw new DOMException((short) 7, "Node is read only");
    }

    @Override // org.w3c.dom.Node
    public Object setUserData(String str, Object obj, UserDataHandler userDataHandler) {
        return null;
    }
}
