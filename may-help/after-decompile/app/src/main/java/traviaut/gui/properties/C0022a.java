package traviaut.gui.properties;

import traviaut.xml.TAAcc;

/* JADX INFO: renamed from: traviaut.gui.properties.a, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/a.class */
public final class C0022a extends w {
    public C0022a(TAAcc tAAcc) {
        super(tAAcc);
        this.a.add(new z("useragent", "User agent:", "fill in UA string of your browser"));
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "Account settings";
    }
}
