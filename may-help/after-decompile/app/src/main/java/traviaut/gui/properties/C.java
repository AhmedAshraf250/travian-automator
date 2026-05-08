package traviaut.gui.properties;

import traviaut.Main;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/C.class */
public final class C extends w {
    public C(Object obj) {
        super(obj);
        this.a.add(new s("tobuild", "supply this village", "send resources from other villages when needed"));
        this.a.add(new s("tocrop", "supply negative crop", "feed troops with crop"));
        this.a.add(new s("upload", "upload resources", "send resources from this village"));
        if (Main.b()) {
            this.a.add(new v("automaxmerch", "merchants for trading route", "how many merchants to send to trading route", 1, 20, 1));
            this.a.add(new v("autoreservemerch", "trading route merchant reserve", "how many merchants should stay at home when sending to trading route", 0, 19, 1));
            this.a.add(new v("period", "trading route period in minutes", "", 1, 1000, 1));
        }
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "trading";
    }
}
