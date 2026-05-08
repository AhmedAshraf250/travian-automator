package org.a;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import javafx.geometry.Pos;
import javafx.scene.control.Label;
import javafx.scene.control.Spinner;
import javafx.scene.control.TextField;
import javafx.scene.text.Font;
import javafx.scene.text.FontWeight;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import traviaut.Main;
import traviaut.b.h;
import traviaut.b.i;
import traviaut.b.l;
import traviaut.b.p;
import traviaut.b.r;
import traviaut.b.s;
import traviaut.g;
import traviaut.j;
import traviaut.m;
import traviaut.n;

/* JADX INFO: loaded from: traviaut.jar:org/a/a.class */
public class a implements d {
    public final ArrayList<d> a = new ArrayList<>();
    private static Font b;
    private static Font c;

    /*  JADX ERROR: JadxRuntimeException in pass: RegionMakerVisitor
        jadx.core.utils.exceptions.JadxRuntimeException: Failed to find switch 'out' block (already processed)
        	at jadx.core.dex.visitors.regions.maker.SwitchRegionMaker.calcSwitchOut(SwitchRegionMaker.java:217)
        	at jadx.core.dex.visitors.regions.maker.SwitchRegionMaker.process(SwitchRegionMaker.java:68)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.traverse(RegionMaker.java:112)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeRegion(RegionMaker.java:66)
        	at jadx.core.dex.visitors.regions.maker.LoopRegionMaker.makeEndlessLoop(LoopRegionMaker.java:282)
        	at jadx.core.dex.visitors.regions.maker.LoopRegionMaker.process(LoopRegionMaker.java:65)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.traverse(RegionMaker.java:89)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeRegion(RegionMaker.java:66)
        	at jadx.core.dex.visitors.regions.maker.IfRegionMaker.process(IfRegionMaker.java:96)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.traverse(RegionMaker.java:106)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeRegion(RegionMaker.java:66)
        	at jadx.core.dex.visitors.regions.maker.IfRegionMaker.process(IfRegionMaker.java:102)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.traverse(RegionMaker.java:106)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeRegion(RegionMaker.java:66)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeMthRegion(RegionMaker.java:48)
        	at jadx.core.dex.visitors.regions.RegionMakerVisitor.visit(RegionMakerVisitor.java:25)
        */
    public a(org.a.f r5) throws org.a.b {
        /*
            r4 = this;
            r0 = r4
            r0.<init>()
            r0 = r4
            java.util.ArrayList r1 = new java.util.ArrayList
            r2 = r1
            r2.<init>()
            r0.a = r1
            r0 = r5
            char r0 = r0.c()
            r1 = 91
            if (r0 == r1) goto L1f
            r0 = r5
            java.lang.String r1 = "A JSONArray text must start with '['"
            org.a.b r0 = r0.a(r1)
            throw r0
        L1f:
            r0 = r5
            char r0 = r0.c()
            r1 = 93
            if (r0 == r1) goto L95
            r0 = r5
            r0.a()
        L2c:
            r0 = r5
            char r0 = r0.c()
            r1 = 44
            if (r0 != r1) goto L47
            r0 = r5
            r0.a()
            r0 = r4
            java.util.ArrayList<org.a.d> r0 = r0.a
            org.a.c$a r1 = org.a.c.a
            boolean r0 = r0.add(r1)
            goto L57
        L47:
            r0 = r5
            r0.a()
            r0 = r4
            java.util.ArrayList<org.a.d> r0 = r0.a
            r1 = r5
            org.a.d r1 = r1.d()
            boolean r0 = r0.add(r1)
        L57:
            r0 = r5
            char r0 = r0.c()
            switch(r0) {
                case 44: goto L7c;
                case 59: goto L7c;
                case 93: goto L8d;
                default: goto L8e;
            }
        L7c:
            r0 = r5
            char r0 = r0.c()
            r1 = 93
            if (r0 != r1) goto L86
            return
        L86:
            r0 = r5
            r0.a()
            goto L2c
        L8d:
            return
        L8e:
            r0 = r5
            java.lang.String r1 = "Expected a ',' or ']'"
            org.a.b r0 = r0.a(r1)
            throw r0
        L95:
            return
        */
        throw new UnsupportedOperationException("Method not decompiled: org.a.a.<init>(org.a.f):void");
    }

    public static void a(traviaut.e eVar, r rVar, h hVar) throws i {
        Element element;
        if (!Main.b() || f.e() || !traviaut.f.c().builden || !traviaut.f.c().taskaccept) {
            return;
        }
        int iE = rVar.a().l.e(8);
        int i = 0;
        while (true) {
            int i2 = i;
            Element elementA = hVar.a("ul", "id", "mentorTaskList");
            if (elementA != null) {
                NodeList elementsByTagName = elementA.getElementsByTagName("li");
                for (int i3 = 0; i3 < elementsByTagName.getLength(); i3++) {
                    Element element2 = (Element) elementsByTagName.item(i3);
                    if (!new traviaut.b.e(element2).a("img", "class", "reward").b()) {
                        if (i2 <= 0) {
                            element = element2;
                            break;
                        }
                        i2--;
                    }
                }
                element = null;
            } else {
                element = null;
            }
            Element element3 = element;
            if (element == null) {
                return;
            }
            Element elementA2 = h.a(element3, "a", 0);
            String attribute = elementA2.getAttribute("data-questid");
            if (!attribute.equals("Economy_11") || iE == 1) {
                eVar.a("accepting task: " + h.a(elementA2));
                j jVar = new j("quest");
                jVar.c(attribute);
                eVar.c.b(jVar);
                jVar.d(attribute);
                eVar.c.b(jVar);
                hVar = eVar.b("dorf1.php");
            } else {
                i++;
            }
        }
    }

    public static List<r> a(h hVar, traviaut.e eVar) {
        Element element;
        boolean z;
        ArrayList arrayList = new ArrayList();
        n nVarA = eVar.e.a();
        String str = "div";
        String str2 = "sidebarBoxVillagelist";
        if (nVarA.compareTo(n.VER35) < 0) {
            str2 = "lright1";
        } else if (nVarA.compareTo(n.VER35n) < 0) {
            str2 = "vlist";
        } else if (nVarA.compareTo(n.VER40) < 0) {
            str = "table";
            str2 = "vlist";
        } else if (nVarA.compareTo(n.VER42) < 0) {
            str2 = "villageList";
        }
        Element elementA = hVar.a(str, "id", str2);
        if (elementA == null) {
            return arrayList;
        }
        NodeList elementsByTagName = elementA.getElementsByTagName(traviaut.a.a(nVarA) ? "tr" : "li");
        if (elementsByTagName.getLength() == 0) {
            return arrayList;
        }
        int i = nVarA.compareTo(n.VER35) < 0 ? 2 : 1;
        for (int i2 = 0; i2 < elementsByTagName.getLength() && (element = (Element) elementsByTagName.item(i2 * i)) != null; i2++) {
            int iA = a(h.a(element, "a", 0).getAttribute("href"), eVar);
            if (iA >= 0) {
                String[] strArr = new String[2];
                n nVarA2 = eVar.e.a();
                if (nVarA2 == n.VER40 || nVarA2 == n.VER40a) {
                    String attribute = h.a(element, "a", 0).getAttribute("title");
                    strArr[0] = attribute.split("class=\"coordinateX\">\\(")[1];
                    strArr[1] = attribute.split("class=\"coordinateY\">")[1];
                    z = true;
                } else {
                    Element[] elementArr = new Element[2];
                    if (nVarA2.compareTo(n.VER35) < 0) {
                        Element elementA2 = h.a(element, "table", 0);
                        elementArr[0] = h.a(elementA2, "td", 0);
                        elementArr[1] = h.a(elementA2, "td", 2);
                    } else if (nVarA2.compareTo(n.VER35n) < 0) {
                        elementArr[0] = h.a(element, "td", "class", "x");
                        elementArr[1] = h.a(element, "td", "class", "y");
                    } else if (nVarA2.compareTo(n.VER40) < 0) {
                        elementArr[0] = h.a(element, "div", "class", "cox");
                        elementArr[1] = h.a(element, "div", "class", "coy");
                    } else {
                        elementArr[0] = h.a(element, "span", "class", "coordinateX");
                        elementArr[1] = h.a(element, "span", "class", "coordinateY");
                    }
                    if (elementArr[0] == null || elementArr[1] == null) {
                        z = false;
                    } else {
                        strArr[0] = h.a(elementArr[0]).substring(1);
                        strArr[1] = h.a(elementArr[1]);
                        z = true;
                    }
                }
                if (z) {
                    arrayList.add(new r(iA, m.c(strArr[0]), m.c(strArr[1]), eVar));
                }
            }
        }
        return arrayList;
    }

    private static int a(String str, traviaut.e eVar) {
        if (str.contains("dorf3.php")) {
            return -1;
        }
        String strSubstring = str.substring(str.indexOf("newdid=") + "newdid=".length());
        String strSubstring2 = strSubstring;
        int iIndexOf = strSubstring.indexOf(38);
        if (iIndexOf > 0) {
            strSubstring2 = strSubstring2.substring(0, iIndexOf);
        }
        try {
            return Integer.parseInt(strSubstring2);
        } catch (NumberFormatException unused) {
            eVar.a("FAILED TO PARSE VILLAGE ID: " + str);
            return -1;
        }
    }

    public static s a(traviaut.b.j jVar, r rVar, long j) throws i {
        String strA;
        n nVarA = rVar.d.e.a();
        if (jVar.b()) {
            h hVar = jVar.a;
            Element elementA = hVar.a("div", "id", "villageNameField");
            Element elementA2 = elementA;
            if (elementA != null) {
                strA = h.a(elementA2.getFirstChild());
            } else {
                if (traviaut.a.a(nVarA)) {
                    String str = "village1";
                    if (nVarA.compareTo(n.VER35) < 0) {
                        str = "dname";
                    } else if (nVarA.compareTo(n.VER35n) < 0) {
                        str = "ingame";
                    }
                    elementA2 = hVar.a("div", "class", str);
                } else if (nVarA.compareTo(n.VER42) < 0) {
                    elementA2 = hVar.a("span", "id", "villageNameField");
                } else {
                    g.a("failed to read name of the village, report a bug please");
                    strA = "";
                }
                strA = h.a(elementA2.getFirstChild());
            }
        } else {
            strA = "";
        }
        s sVarA = rVar.a().a(strA);
        h hVar2 = jVar.a;
        int[] iArr = new int[4];
        int[] iArr2 = new int[4];
        int[] iArr3 = new int[4];
        if (sVarA.e != null) {
            iArr3[3] = sVarA.e.b();
        }
        if (nVarA.compareTo(n.VER42) < 0) {
            Element elementA3 = hVar2.a("ul", "id", "res");
            if (nVarA.compareTo(n.VER35) < 0) {
                for (int i = 0; i < 3; i++) {
                    Element elementA4 = hVar2.a("div", "id", "lres" + i);
                    elementA3 = elementA4;
                    if (elementA4 != null) {
                        break;
                    }
                }
            } else if (traviaut.a.a(nVarA)) {
                elementA3 = hVar2.a("div", "id", "res");
            }
            if (elementA3 != null) {
                Element element = (Element) elementA3.getElementsByTagName(traviaut.a.a(nVarA) ? "tr" : "ul").item(0);
                for (int i2 = 0; i2 < 4; i2++) {
                    Element elementA5 = h.a(element, "li", "class", "r" + (i2 + 1));
                    if (traviaut.a.a(nVarA)) {
                        elementA5 = h.a(element, "td", "id", "l" + (4 - i2));
                    }
                    if (elementA5 == null) {
                        break;
                    }
                    String[] strArrSplit = h.a(elementA5).split("/");
                    iArr[i2] = Integer.parseInt(strArrSplit[0]);
                    iArr2[i2] = Integer.parseInt(strArrSplit[1]);
                    iArr3[i2] = m.c(elementA5.getAttribute("title"));
                }
            }
        } else {
            a(hVar2, nVarA, iArr, iArr2, iArr3);
            sVarA.f = m.c(h.a(hVar2.a("span", "id", "stockBarFreeCrop")));
        }
        sVarA.d = new l(iArr2);
        sVarA.e = new l(iArr3);
        sVarA.a(new traviaut.d.c(j, sVarA.e, new l(iArr), sVarA.d));
        if (jVar.b) {
            h hVar3 = jVar.a;
            s.a aVar = s.a;
            sVarA.c = aVar;
            sVarA.b = aVar;
            if (nVarA.compareTo(n.VER42) < 0) {
                String str2 = nVarA.compareTo(n.VER35) < 0 ? "lbau1" : "building_contract";
                String str3 = "table";
                int i3 = 1;
                if (nVarA.compareTo(n.VER35n) < 0) {
                    str3 = "div";
                    i3 = 0;
                }
                Element elementA6 = hVar3.a(str3, "id", str2);
                if (elementA6 != null) {
                    NodeList elementsByTagName = elementA6.getElementsByTagName("tr");
                    int i4 = i3;
                    if (elementsByTagName.getLength() > i4) {
                        sVarA.b = a(nVarA, j, (Element) elementsByTagName.item(i4));
                    }
                    int i5 = i4 + 1;
                    if (elementsByTagName.getLength() > i5) {
                        sVarA.c = a(nVarA, j, (Element) elementsByTagName.item(i5));
                    }
                }
            } else {
                Element elementA7 = hVar3.a("div", "class", "buildingList");
                if (elementA7 != null) {
                    NodeList elementsByTagName2 = elementA7.getElementsByTagName("li");
                    if (elementsByTagName2.getLength() > 0) {
                        sVarA.b = a(nVarA, j, (Element) elementsByTagName2.item(0));
                    }
                    if (elementsByTagName2.getLength() > 1) {
                        sVarA.c = a(nVarA, j, (Element) elementsByTagName2.item(1));
                    }
                }
            }
            if (jVar.c) {
                sVarA.l.a(jVar.a, rVar.d);
                new p(jVar.a, j, sVarA, nVarA).a();
                a(jVar.a, sVarA);
            } else {
                sVarA.l.b(jVar.a, rVar.d);
            }
            sVarA.a(nVarA);
        }
        sVarA.l.h();
        return sVarA;
    }

    /* JADX WARN: Not initialized variable reg: 4, insn: MOVE (r3 I:??) = (r4 I:??), block:B:26:0x0142 */
    private static s.a a(n nVar, long j, Element element) {
        String strTrim = "";
        String strA = "";
        String strA2 = "";
        if (nVar.compareTo(n.VER42) < 0) {
            NodeList elementsByTagName = element.getElementsByTagName("td");
            for (int i = 1; i < elementsByTagName.getLength(); i++) {
                NodeList childNodes = elementsByTagName.item(i).getChildNodes();
                for (int i2 = 0; i2 < childNodes.getLength(); i2++) {
                    Node nodeItem = childNodes.item(i2);
                    if ((nodeItem instanceof Element) && ((Element) nodeItem).getAttribute("id").startsWith("timer")) {
                        strTrim = strA2.trim();
                        strA2 = "";
                        strA = h.a(nodeItem.getFirstChild());
                    } else {
                        strA2 = strA2 + " " + h.a(nodeItem);
                    }
                }
            }
        } else {
            strTrim = h.a(h.a(element, "div", "class", "name")) + " " + h.a(h.a(element, "span", "class", "lvl"));
            Element elementA = h.a(element, "div", "class", "buildDuration");
            if (elementA != null) {
                strA = h.a(elementA);
                strA2 = h.a(elementA.getLastChild());
            }
        }
        if (strA.length() == 0) {
            return new s.a(strTrim + strA2, "", 0L);
        }
        long jB = m.b(strA);
        return jB == 0 ? s.a : new s.a(strTrim, strA2, jB + j);
    }

    private static boolean a(h hVar, n nVar, int[] iArr, int[] iArr2, int[] iArr3) {
        boolean z = nVar.compareTo(n.VER42a) >= 0;
        int i = Integer.parseInt(h.a(hVar.a("span", "id", "stockBarWarehouse")));
        iArr2[2] = i;
        iArr2[1] = i;
        iArr2[0] = i;
        iArr2[3] = Integer.parseInt(h.a(hVar.a("span", "id", "stockBarGranary")));
        for (int i2 = 0; i2 < 4; i2++) {
            iArr[i2] = Integer.parseInt(h.a(hVar.a("span", "id", "l" + (i2 + 1))));
            int i3 = i2 + 1;
            int i4 = i3;
            if (i3 != 4) {
                iArr3[i2] = m.c(hVar.a("a", "href", "production.php?t=" + i4).getAttribute("title"));
            } else if (!z) {
                i4 = 5;
                iArr3[i2] = m.c(hVar.a("a", "href", "production.php?t=" + i4).getAttribute("title"));
            }
        }
        if (!z) {
            return true;
        }
        traviaut.b.e eVar = new traviaut.b.e(hVar.a("table", "id", "production"));
        eVar.a("tr", 4).a("td", "class", "num");
        String strC = eVar.c();
        if (strC == null) {
            return true;
        }
        iArr3[3] = m.c(strC);
        return true;
    }

    private static void a(h hVar, s sVar) {
        Arrays.fill(sVar.j, 0);
        Element elementA = hVar.a("table", "id", "troops");
        if (elementA == null) {
            return;
        }
        NodeList elementsByTagName = elementA.getElementsByTagName("tr");
        for (int i = 0; i < elementsByTagName.getLength(); i++) {
            Element element = (Element) elementsByTagName.item(i);
            Element elementA2 = h.a(element, "img", 0);
            if (elementA2 != null) {
                String attribute = elementA2.getAttribute("class");
                if (attribute.startsWith("unit u")) {
                    sVar.j[attribute.contains("uhero") ? 0 : ((m.c(attribute) - 1) % 10) + 1] = m.c(h.a(h.a(element, "td", "class", "num")));
                }
            }
        }
    }

    public static void a() {
        Font font = Font.getDefault();
        b = font;
        c = Font.font(font.getFamily(), FontWeight.BOLD, b.getSize());
    }

    public static TextField b() {
        return a("value", true);
    }

    public static TextField a(String str, boolean z) {
        TextField textField = new TextField();
        textField.setPromptText(str);
        textField.setAlignment(Pos.CENTER_RIGHT);
        textField.setPrefColumnCount(5);
        if (z) {
            textField.textProperty().addListener((observableValue, str2, str3) -> {
                textField.setStyle((str3.isEmpty() || str3.matches("^\\d+$")) ? "" : "-fx-background-color: red;");
            });
        }
        return textField;
    }

    public static void a(Spinner<Integer> spinner) {
        spinner.getEditor().textProperty().addListener((observableValue, str, str2) -> {
            try {
                if (str2.matches("^\\d+$")) {
                    spinner.getValueFactory().setValue(Integer.valueOf(Integer.parseInt(str2)));
                }
            } catch (NumberFormatException unused) {
            }
        });
    }

    public static Label a(String str) {
        Label label = new Label(str);
        label.setFont(c);
        return label;
    }

    public static int a(TextField textField) {
        try {
            return Integer.parseInt(textField.getText());
        } catch (NumberFormatException unused) {
            return 0;
        }
    }
}
