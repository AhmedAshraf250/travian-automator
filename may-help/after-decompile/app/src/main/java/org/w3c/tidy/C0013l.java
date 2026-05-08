package org.w3c.tidy;

import org.w3c.dom.CharacterData;
import org.w3c.dom.DOMException;

/* JADX INFO: renamed from: org.w3c.tidy.l, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/l.class */
public class C0013l extends C0018q implements CharacterData {
    protected C0013l(C c) {
        super(c);
    }

    @Override // org.w3c.dom.CharacterData
    public String getData() throws DOMException {
        return getNodeValue();
    }

    @Override // org.w3c.dom.CharacterData
    public int getLength() {
        int i = 0;
        if (this.b.g != null && this.b.e < this.b.f) {
            i = this.b.f - this.b.e;
        }
        return i;
    }

    @Override // org.w3c.dom.CharacterData
    public String substringData(int i, int i2) throws DOMException {
        String strA = null;
        if (i2 < 0) {
            throw new DOMException((short) 1, "Invalid length");
        }
        if (this.b.g != null && this.b.e < this.b.f) {
            if (this.b.e + i >= this.b.f) {
                throw new DOMException((short) 1, "Invalid offset");
            }
            int i3 = i2;
            if (((this.b.e + i) + i2) - 1 >= this.b.f) {
                i3 = (this.b.f - this.b.e) - i;
            }
            strA = S.a(this.b.g, this.b.e + i, i3);
        }
        return strA;
    }

    @Override // org.w3c.dom.CharacterData
    public void setData(String str) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.dom.CharacterData
    public void appendData(String str) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.dom.CharacterData
    public void insertData(int i, String str) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.dom.CharacterData
    public void deleteData(int i, int i2) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }

    @Override // org.w3c.dom.CharacterData
    public void replaceData(int i, int i2, String str) throws DOMException {
        throw new DOMException((short) 7, "Not supported");
    }
}
