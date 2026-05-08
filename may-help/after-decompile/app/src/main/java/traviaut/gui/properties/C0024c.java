package traviaut.gui.properties;

import traviaut.xml.TACeleb;

/* JADX INFO: renamed from: traviaut.gui.properties.c, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/c.class */
public final class C0024c extends w {
    public C0024c(TACeleb tACeleb) {
        super(tACeleb);
        this.a.add(new t("type", "Prefered celebration type: ", TACeleb.CelebType.values()));
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "Celebrations";
    }
}
