package org.w3c.tidy;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/v.class */
public final class v {
    protected String a;
    protected short b;
    protected int c;
    private I d;
    private O e;

    public v(String str, short s, int i, I i2, O o) {
        this.a = str;
        this.b = s;
        this.c = i;
        this.d = i2;
        this.e = o;
    }

    public final O a() {
        return this.e;
    }

    public final I b() {
        return this.d;
    }

    public final void a(O o) {
        this.e = o;
    }

    public final void a(I i) {
        this.d = i;
    }
}
