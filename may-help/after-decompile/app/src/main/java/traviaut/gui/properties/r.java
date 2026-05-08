package traviaut.gui.properties;

import traviaut.xml.TAAcc;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/r.class */
public final class r extends w {
    public r(TAAcc tAAcc) {
        super(tAAcc);
        this.a.add(new v("nx", "new village X:", "", -400, 400, 1));
        this.a.add(new v("ny", "new village Y:", "", -400, 400, 1));
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "New village";
    }
}
