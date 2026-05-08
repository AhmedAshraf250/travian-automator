package org.w3c.tidy;

import org.w3c.dom.Comment;

/* JADX INFO: renamed from: org.w3c.tidy.m, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/m.class */
public final class C0014m extends C0013l implements Comment {
    protected C0014m(C c) {
        super(c);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final String getNodeName() {
        return "#comment";
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final short getNodeType() {
        return (short) 8;
    }
}
