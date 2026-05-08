package traviaut.a;

import java.util.Iterator;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import traviaut.b.s;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/g.class */
public final class g extends b {
    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        if (rVar.a().l.e(15) == 0) {
            return false;
        }
        return rVar.e.builder.enabled();
    }

    @Override // traviaut.a.b
    public final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        a aVar = new a(rVar, (byte) 0);
        if (aVar.a(j)) {
            aVar.a(lVar);
        }
    }

    @Override // traviaut.a.b
    public final boolean b(traviaut.b.r rVar) {
        return rVar.a().l.a(rVar.d) && !rVar.b(true).isEmpty();
    }

    /* JADX INFO: loaded from: traviaut.jar:traviaut/a/g$a.class */
    static class a {
        private boolean a;
        private boolean b;
        private final traviaut.b.r c;

        private a(traviaut.b.r rVar) {
            this.a = true;
            this.b = true;
            this.c = rVar;
        }

        public final boolean a(long j) {
            a(j, this.c.a().b);
            a(j, this.c.a().c);
            return a();
        }

        private void a(long j, s.a aVar) {
            int iA = aVar.a();
            if (iA != 0 && aVar.a.b() - j >= traviaut.b.r.g()) {
                if (this.c.d.e.c().c) {
                    this.b = false;
                    this.a = false;
                }
                if (iA < 19) {
                    this.a = false;
                } else {
                    this.b = false;
                }
            }
        }

        private boolean a() {
            return this.a || this.b;
        }

        /* synthetic */ a(traviaut.b.r rVar, byte b) {
            this(rVar);
        }

        /* JADX WARN: Removed duplicated region for block: B:13:0x0055  */
        /*
            Code decompiled incorrectly, please refer to instructions dump.
            To view partially-correct code enable 'Show inconsistent code' option in preferences
        */
        public final void a(traviaut.b.l r6) {
            /*
                r5 = this;
                r0 = r5
                traviaut.b.r r0 = r0.c
                r1 = 0
                java.util.Set r0 = r0.b(r1)
                java.util.Iterator r0 = r0.iterator()
                r7 = r0
            Le:
                r0 = r7
                boolean r0 = r0.hasNext()
                if (r0 == 0) goto L7c
                r0 = r7
                java.lang.Object r0 = r0.next()
                java.lang.Integer r0 = (java.lang.Integer) r0
                int r0 = r0.intValue()
                r8 = r0
                r0 = r5
                r1 = r8
                r10 = r1
                r9 = r0
                r0 = r10
                r1 = 19
                if (r0 >= r1) goto L43
                r0 = r9
                boolean r0 = r0.a
                if (r0 == 0) goto L55
                r0 = r9
                r1 = 0
                r0.a = r1
                r0 = 1
                goto L56
            L43:
                r0 = r9
                boolean r0 = r0.b
                if (r0 == 0) goto L55
                r0 = r9
                r1 = 0
                r0.b = r1
                r0 = 1
                goto L56
            L55:
                r0 = 0
            L56:
                if (r0 == 0) goto L72
                r0 = r6
                r1 = r5
                traviaut.b.r r1 = r1.c
                traviaut.b.s r1 = r1.a()
                traviaut.b.b r1 = r1.l
                r2 = r5
                traviaut.b.r r2 = r2.c
                traviaut.e r2 = r2.d
                r3 = r8
                traviaut.b.l r1 = r1.a(r2, r3)
                r0.a(r1)
            L72:
                r0 = r5
                boolean r0 = r0.a()
                if (r0 == 0) goto L7c
                goto Le
            L7c:
                return
            */
            throw new UnsupportedOperationException("Method not decompiled: traviaut.a.g.a.a(traviaut.b.l):void");
        }
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        traviaut.b.h hVarB;
        String attribute;
        Element elementA;
        Element elementA2;
        aVar.b.a(false);
        traviaut.b.b bVar = aVar.b.a().l;
        Iterator<Integer> it = aVar.b.b(true).iterator();
        while (it.hasNext()) {
            int iIntValue = it.next().intValue();
            if (iIntValue >= 0) {
                traviaut.n nVarA = aVar.c.e.a();
                traviaut.b.h hVarB2 = aVar.c.b(traviaut.b.b.a(iIntValue));
                if (traviaut.n.VER40a.ordinal() <= nVarA.ordinal()) {
                    traviaut.e eVar = aVar.c;
                    Element elementA3 = hVarB2.a("div", "class", "contentNavi");
                    traviaut.b.h hVarB3 = (elementA3 == null || (elementA = traviaut.b.h.a(elementA3, "div", "class", "active")) == null || traviaut.b.h.a(elementA, "div", "class", "favorKey0") != null || (elementA2 = hVarB2.a("div", "class", "favorKey0")) == null) ? hVarB2 : eVar.b(traviaut.b.h.a(elementA2, "a", 0).getAttribute("href"));
                    hVarB2 = hVarB3;
                }
                NodeList elementsByTagName = hVarB2.a("div", "id", nVarA == traviaut.n.VER31 ? "lmid2" : "content").getElementsByTagName(traviaut.a.a(nVarA) ? "a" : "button");
                for (int i = 0; i < elementsByTagName.getLength(); i++) {
                    Element element = (Element) elementsByTagName.item(i);
                    if (traviaut.b.h.a(element, "img", "class", "npc") == null && !element.getAttribute("class").contains("builder")) {
                        if (traviaut.a.a(nVarA)) {
                            attribute = element.getAttribute("href");
                        } else {
                            String[] strArrSplit = element.getAttribute("onclick").split("'");
                            if (strArrSplit.length >= 2) {
                                attribute = strArrSplit[1];
                            } else {
                                continue;
                            }
                        }
                        if (!attribute.isEmpty() && attribute.indexOf("c=") >= 0 && attribute.indexOf("d=") < 0) {
                            aVar.b.a("building " + iIntValue + " " + aVar.b.a().l.d(iIntValue).a());
                            hVarB = aVar.c.b(attribute);
                            break;
                        }
                    }
                }
                hVarB = null;
            } else {
                hVarB = null;
            }
            traviaut.b.h hVar = hVarB;
            if (hVarB != null) {
                bVar.c(iIntValue);
                aVar.b.a(new traviaut.b.j(hVar, iIntValue < 19));
                return;
            }
        }
    }
}
