package traviaut.b;

import java.util.HashMap;
import java.util.Map;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/p.class */
public final class p {
    private static final Map<String, Integer> a;
    private final h b;
    private final long c;
    private final s d;
    private final traviaut.n e;

    public p(h hVar, long j, s sVar, traviaut.n nVar) {
        this.b = hVar;
        this.c = j;
        this.d = sVar;
        this.e = nVar;
    }

    static {
        HashMap map = new HashMap();
        a = map;
        map.put("a1", 5);
        a.put("a2", 4);
        a.put("a3", 6);
        a.put("d1", 3);
        a.put("d2", 4);
        a.put("d3", 6);
        a.put("adventure", 7);
        a.put("settle", 7);
    }

    public final void a() {
        this.d.k.clear();
        Element elementA = this.b.a("div", "class", "movements");
        if (this.e.compareTo(traviaut.n.VER40) < 0) {
            elementA = this.b.a("table", "id", "movements");
        } else if (this.e.compareTo(traviaut.n.VER35n) < 0) {
            elementA = this.b.a("div", "id", "troop_movements");
        } else if (this.e.compareTo(traviaut.n.VER35) < 0) {
            Element elementA2 = this.b.a("div", "id", "ltbw0");
            elementA = elementA2;
            if (elementA2 == null) {
                this.b.a("div", "id", "ltbw1");
            }
        }
        if (elementA == null) {
            return;
        }
        NodeList elementsByTagName = elementA.getElementsByTagName("tr");
        for (int i = 0; i < elementsByTagName.getLength(); i++) {
            Element element = (Element) elementsByTagName.item(i);
            NodeList elementsByTagName2 = element.getElementsByTagName(this.e.compareTo(traviaut.n.VER35n) < 0 ? "tr" : "span");
            if (elementsByTagName2.getLength() >= 2) {
                if (this.e.compareTo(traviaut.n.VER35n) < 0) {
                    String strA = h.a(elementsByTagName2.item(4).getFirstChild());
                    String strA2 = h.a(elementsByTagName2.item(1).getFirstChild());
                    String strA3 = h.a(elementsByTagName2.item(2).getFirstChild());
                    Element elementA3 = h.a(element, "b", "style", "text-align: right;");
                    if (this.e.compareTo(traviaut.n.VER35) < 0) {
                        elementA3 = h.a(element, "b", "align", "right");
                    }
                    String str = elementA3.getAttribute("class").split(" ")[0];
                    this.d.a(strA2, strA3, strA, 'c' == str.charAt(0) ? Integer.valueOf(str.substring(1)).intValue() : 0, this.c);
                } else {
                    int length = elementsByTagName2.getLength() - 1;
                    Element element2 = (Element) elementsByTagName2.item(0);
                    String strA4 = h.a(elementsByTagName2.item(length));
                    String strA5 = h.a(element2);
                    String strA6 = length > 1 ? h.a(elementsByTagName2.item(1)) : "";
                    Integer num = a.get(element2.getAttribute("class"));
                    this.d.a(strA5, strA6, strA4, num != null ? num.intValue() : 0, this.c);
                }
            }
        }
    }
}
