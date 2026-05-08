package org.w3c.tidy;

import org.w3c.dom.DOMException;
import org.w3c.dom.ProcessingInstruction;

/* JADX INFO: renamed from: org.w3c.tidy.t, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/t.class */
public final class C0021t extends C0018q implements ProcessingInstruction {
    protected C0021t(C c) {
        super(c);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public final short getNodeType() {
        return (short) 7;
    }

    @Override // org.w3c.dom.ProcessingInstruction
    public final String getTarget() {
        return null;
    }

    @Override // org.w3c.dom.ProcessingInstruction
    public final String getData() {
        return getNodeValue();
    }

    @Override // org.w3c.dom.ProcessingInstruction
    public final void setData(String str) throws DOMException {
        throw new DOMException((short) 7, "Node is read only");
    }
}
