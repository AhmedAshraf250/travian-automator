package traviaut.gui.properties;

import traviaut.Main;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/j.class */
public final class j extends w {
    public j(Object obj) {
        super(obj);
        this.a.add(new z("userID", "Your licence key:", "fill in your licence key if you have one"));
        this.a.add(new z("useragent", "User agent:", "fill in UA string of your browser"));
        if (Main.a()) {
            this.a.add(new s("generateua", "Generate User Agent per account:", ""));
        }
        this.a.add(new v("tradelimit", "Amount of minimal trade in % of hour production:", "<html>\nSets the minimal amount to trade as percent of production per hour.\n<br>\nUnlimited when set to zero.\n</html>", 0, 100, 5));
        this.a.add(new v("period", "Update period in minutes:", "village refresh period", 1, 999, 1));
        this.a.add(new v("bslimit", "Warehouse reserve in hrs of production:", "how much space to keep during trading", 0, 999, 1));
        if (Main.b()) {
            this.a.add(new s("checkoverflow", "Avoid resources overflow", "resources will be sent away if they would overflow within one hour"));
        }
        if (Main.a()) {
            this.a.add(new v("tradedistlimit", "Maximal trading distance:", "", 0, 999, 1));
        }
        this.a.add(new v("cropFactor", "Crop factor in %:", "TA will keep crop at this level compared to other resources", 1, 999, 10));
        if (Main.b()) {
            this.a.add(new s("readreports", "Read reports", "TA will randomly read reports (to pretend human behaviour)"));
            this.a.add(new s("readmessages", "Read messages", "TA will randomly read messages (to pretend human behaviour)"));
        }
        this.a.add(new s("refreshbuild", "Refresh after build", ""));
        this.a.add(new s("refreshattack", "Refresh after attack", ""));
        if (Main.a()) {
            this.a.add(new v("resmaxlvl", "Maximal resource level:", "Limits level of resource fields", 0, 100, 1));
        }
        this.a.add(new s("randperiod", "Random refresh period", ""));
        if (Main.a()) {
            this.a.add(new s("settlersDefault", "Enable settlers in a new village by default", "When new village appears, TA can switch on settlers there, so you don't have to click"));
        }
        if (Main.b()) {
            this.a.add(new s("taskaccept", "Accept quest tasks", ""));
        }
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "general";
    }
}
