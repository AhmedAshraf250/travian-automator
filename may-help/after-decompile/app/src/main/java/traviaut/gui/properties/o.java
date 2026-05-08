package traviaut.gui.properties;

import traviaut.xml.TAGlobalSets;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/o.class */
public final class o extends w {
    public o(TAGlobalSets tAGlobalSets) {
        super(tAGlobalSets);
        this.a.add(new z("userID", "Your licence key:", "fill in your licence key if you have one"));
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "License Key";
    }
}
