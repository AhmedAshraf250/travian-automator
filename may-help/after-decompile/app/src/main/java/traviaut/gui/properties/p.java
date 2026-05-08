package traviaut.gui.properties;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/p.class */
public final class p extends w {
    public p(Object obj) {
        super(obj);
        this.a.add(new s("logouten", "Enable automatic logout", "TA will logout periodically"));
        this.a.add(new v("loginTime", "login period in minutes:", "", 1, 1000000, 1));
        this.a.add(new v("logoutTime", "logout period in minutes:", "", 1, 1000000, 1));
        this.a.add(new v("logoutVar", "variability of times in %:", "", 0, 100, 1));
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "logout settings";
    }
}
