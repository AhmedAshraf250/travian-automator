package traviaut.a;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import traviaut.Main;
import traviaut.k;
import traviaut.xml.TAHero;
import traviaut.xml.TAHeroItem;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/c.class */
public final class c extends u {
    private static final long b = TimeUnit.HOURS.toMillis(1);

    @Override // traviaut.a.u
    public final boolean a(traviaut.e eVar) {
        return Main.b() && eVar.c().auctionsell && eVar.e.j && System.currentTimeMillis() >= this.a;
    }

    @Override // traviaut.a.u
    public final long b(k.a aVar) throws traviaut.b.i {
        traviaut.e eVar = aVar.c;
        traviaut.b.h hVarB = eVar.b((eVar.e.c ? "hero_auction.php?" : "hero.php?t=4&") + "action=sell");
        Element elementA = hVarB.a("div", "id", "itemsToSale");
        if (elementA == null) {
            return b;
        }
        if (hVarB.a("div", "class", "auctionAdventureBar") != null) {
            return 0L;
        }
        NodeList childNodes = elementA.getChildNodes();
        TAHero tAHero = traviaut.f.c().hero;
        int[] iArr = new int[250];
        LinkedHashMap linkedHashMap = new LinkedHashMap();
        for (int i = 0; i < childNodes.getLength(); i++) {
            Element element = (Element) childNodes.item(i);
            int iC = traviaut.m.c(element.getAttribute("id").substring(5));
            Element element2 = (Element) element.getFirstChild();
            if (element2 != null) {
                int iA = a(element2.getAttribute("class"));
                linkedHashMap.put(Integer.valueOf(iC), Integer.valueOf(iA));
                tAHero.getItem(iA);
                iArr[iA] = iArr[iA] + traviaut.m.c(traviaut.b.h.a(element2.getFirstChild()));
            }
        }
        traviaut.j jVar = new traviaut.j("heroAuction");
        Element elementA2 = hVarB.a("form", "id", "sellForm");
        if (elementA2 == null) {
            return b;
        }
        traviaut.j jVar2 = new traviaut.j(elementA2);
        int length = new traviaut.b.e(hVarB.a("table", "class", "sellings")).a("tbody").b("tr").getLength();
        for (Map.Entry entry : linkedHashMap.entrySet()) {
            if (length >= 5) {
                break;
            }
            TAHeroItem item = tAHero.getItem(((Integer) entry.getValue()).intValue());
            if (item.sell) {
                boolean zA = traviaut.b.g.a(item.id);
                int i2 = zA ? 10 : 1;
                if (iArr[item.id] - item.reserve >= i2) {
                    jVar.c(item.id);
                    org.a.c cVarB = eVar.c.b(jVar).b("response");
                    if (cVarB.c("error").equals("true")) {
                        eVar.a("failed to retrieve item info: " + cVarB.c("errorMsg"));
                        return b;
                    }
                    ArrayList<org.a.d> arrayList = ((org.a.a) cVarB.a("data")).a;
                    int i3 = zA ? 1 : 0;
                    if (arrayList.size() > i3 && traviaut.m.c(arrayList.get(i3).toString()) == i2) {
                        eVar.a("selling " + traviaut.b.g.a.get(Integer.valueOf(item.id)));
                        jVar2.c(((Integer) entry.getKey()).intValue(), i2);
                        jVar2 = new traviaut.j(eVar.a(jVar2).a("form", "id", "sellForm"));
                        int i4 = item.id;
                        iArr[i4] = iArr[i4] - i2;
                        length++;
                    }
                } else {
                    continue;
                }
            }
        }
        return b;
    }

    private static int a(String str) {
        for (String str2 : str.split(" ")) {
            String strSubstring = str2.startsWith("female_item") ? str2.substring(12) : null;
            if (str2.startsWith("male_item")) {
                strSubstring = str2.substring(10);
            }
            if (strSubstring != null) {
                return traviaut.m.c(strSubstring);
            }
        }
        return 0;
    }
}
