package traviaut;

import org.w3c.dom.Element;

/* JADX INFO: loaded from: traviaut.jar:traviaut/l.class */
public enum l {
    UNKNOWN("unknown", 0, true, 0, 0),
    GAUL("d2_1", 33, true, 750, 10),
    ROMAN("d2_11", 31, false, 500, 20),
    TEUTON("d2_12", 32, true, 1000, 10);

    private String i;
    public final int b;
    public final boolean c;
    public final int d;
    public final int e;

    l(String str, int i, boolean z, int i2, int i3) {
        this.i = str;
        this.b = i;
        this.c = z;
        this.d = i2;
        this.e = i3;
    }

    public final int a() {
        if (this.b == 0) {
            return 0;
        }
        return this.b - 31;
    }

    public static l a(traviaut.b.h hVar) {
        for (l lVar : values()) {
            if (lVar != UNKNOWN) {
                Element elementA = hVar.a("div", "class", lVar.i);
                Element elementA2 = hVar.a("img", "class", "g" + lVar.b + "Top");
                Element elementA3 = hVar.a("img", "class", "nation" + (lVar.b - 30));
                if (elementA != null || elementA2 != null || elementA3 != null) {
                    return lVar;
                }
            }
        }
        return UNKNOWN;
    }
}
