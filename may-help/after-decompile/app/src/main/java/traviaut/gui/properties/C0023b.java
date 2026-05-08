package traviaut.gui.properties;

import traviaut.Main;

/* JADX INFO: renamed from: traviaut.gui.properties.b, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/b.class */
public final class C0023b extends w {
    public C0023b(Object obj) {
        super(obj);
        this.a.add(new s("upresources", "upgrade resources", ""));
        this.a.add(new s("upbuildings", "upgrade buildings", ""));
        if (Main.a()) {
            this.a.add(new s("newbuilds", "new buildings", "build new buildings according to village layout"));
        }
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "builder";
    }
}
