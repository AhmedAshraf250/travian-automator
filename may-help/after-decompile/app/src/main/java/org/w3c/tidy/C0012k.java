package org.w3c.tidy;

import org.w3c.dom.CDATASection;

/* JADX INFO: renamed from: org.w3c.tidy.k, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/k.class */
public final class C0012k extends u implements CDATASection {
    protected C0012k(C c) {
        super(c);
    }

    @Override // org.w3c.tidy.u, org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final String getNodeName() {
        return "#cdata-section";
    }

    @Override // org.w3c.tidy.u, org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final short getNodeType() {
        return (short) 4;
    }
}
