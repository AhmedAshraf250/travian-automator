package org.w3c.tidy;

import org.w3c.dom.DocumentType;
import org.w3c.dom.NamedNodeMap;

/* JADX INFO: renamed from: org.w3c.tidy.o, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/o.class */
public final class C0016o extends C0018q implements DocumentType {
    protected C0016o(C c) {
        super(c);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final short getNodeType() {
        return (short) 10;
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final String getNodeName() {
        return getName();
    }

    @Override // org.w3c.dom.DocumentType
    public final String getName() {
        String strA = null;
        if (this.b.h == 1 && this.b.g != null && this.b.e < this.b.f) {
            strA = S.a(this.b.g, this.b.e, this.b.f - this.b.e);
        }
        return strA;
    }

    @Override // org.w3c.dom.DocumentType
    public final NamedNodeMap getEntities() {
        return null;
    }

    @Override // org.w3c.dom.DocumentType
    public final NamedNodeMap getNotations() {
        return null;
    }

    @Override // org.w3c.dom.DocumentType
    public final String getPublicId() {
        return null;
    }

    @Override // org.w3c.dom.DocumentType
    public final String getSystemId() {
        return null;
    }

    @Override // org.w3c.dom.DocumentType
    public final String getInternalSubset() {
        return null;
    }
}
