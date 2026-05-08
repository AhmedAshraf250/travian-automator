package org.w3c.tidy;

import org.w3c.dom.DOMException;
import org.w3c.dom.Text;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/u.class */
public class u extends C0013l implements Text {
    protected u(C c) {
        super(c);
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public String getNodeName() {
        return "#text";
    }

    @Override // org.w3c.tidy.C0018q, org.w3c.dom.Node
    public short getNodeType() {
        return (short) 3;
    }

    @Override // org.w3c.dom.Text
    public Text splitText(int i) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.dom.Text
    public String getWholeText() {
        return null;
    }

    @Override // org.w3c.dom.Text
    public boolean isElementContentWhitespace() {
        return false;
    }

    @Override // org.w3c.dom.Text
    public Text replaceWholeText(String str) throws DOMException {
        return this;
    }
}
