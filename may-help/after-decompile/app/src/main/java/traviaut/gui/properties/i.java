package traviaut.gui.properties;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/i.class */
public final class i extends w {
    public i(GTData gTData) {
        super(gTData);
        this.a.add(new v("xcoord", "X:", "", -500, 500, 1));
        this.a.add(new v("ycoord", "Y:", "", -500, 500, 1));
        this.a.add(new v("amount", "total amount in thousands (1000x)", "", 1, 10000, 1));
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "Global Trader";
    }
}
