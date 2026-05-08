package traviaut.b;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.Iterator;
import java.util.List;
import java.util.Set;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import traviaut.xml.TABuildLayout;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/b.class */
public final class b {
    private int a;
    private boolean f;
    private int h;
    private int i;
    private final int[] b = new int[41];
    private final int[] c = new int[42];
    private final d[] d = new d[41];
    private final d[] e = new d[41];
    private boolean g = true;
    private boolean j = false;

    public b() {
        Arrays.fill(this.d, new d());
        Arrays.fill(this.e, new d());
    }

    public final void a(b bVar) {
        bVar.a = this.a;
        System.arraycopy(this.b, 0, bVar.b, 0, 41);
        System.arraycopy(this.d, 0, bVar.d, 0, 41);
        System.arraycopy(this.e, 0, bVar.e, 0, 41);
        bVar.j = this.j;
        bVar.h = this.h;
        bVar.i = this.i;
    }

    public final boolean a() {
        return this.f;
    }

    public final boolean b() {
        return this.a > 0;
    }

    public final boolean c() {
        return this.j;
    }

    public final boolean e() {
        return this.h > 0;
    }

    public final boolean f() {
        return this.i > 0;
    }

    public final boolean a(traviaut.e eVar) {
        boolean z = this.h > 0;
        boolean zF = f();
        if (eVar.e.c().c) {
            return (z || zF) ? false : true;
        }
        if (zF) {
            return (z || this.g) ? false : true;
        }
        return true;
    }

    public final boolean b(traviaut.e eVar) {
        if (f()) {
            return false;
        }
        return (eVar.e.c().c && e()) ? false : true;
    }

    public final int g() {
        for (int i = 19; i <= 38; i++) {
            if (this.b[i] == 0) {
                return i;
            }
        }
        return 0;
    }

    public static String a(int i) {
        return "build.php?id=" + i;
    }

    public static String b(int i) {
        return "build.php?gid=" + i;
    }

    public final void a(traviaut.e eVar, List<traviaut.gui.k> list, TABuildLayout tABuildLayout) {
        d[] dVarArr = this.e;
        for (int i = 0; i < 41; i++) {
            d dVar = dVarArr[i];
            if (!dVar.b(eVar, this.f)) {
                list.add(new traviaut.gui.k(dVar.a, dVar.a(), tABuildLayout.isSet(dVar.a)));
            }
        }
    }

    public final List<String> a(traviaut.e eVar, Set<Integer> set, l lVar) {
        ArrayList arrayList = new ArrayList();
        Iterator<Integer> it = set.iterator();
        while (it.hasNext()) {
            int iIntValue = it.next().intValue();
            String strA = this.e[iIntValue].a();
            if (!this.e[iIntValue].a(eVar, lVar)) {
                strA = strA + " XXX";
            }
            arrayList.add(strA);
        }
        return arrayList;
    }

    public final l a(traviaut.e eVar, int i) {
        return this.e[i].a(eVar, true);
    }

    private void c(h hVar, traviaut.e eVar) {
        if (this.a < 0) {
            return;
        }
        a(hVar, eVar, "clickareas");
        a(hVar, eVar, "rx");
        a(hVar, eVar, "map1");
        a(hVar, eVar, "map2");
    }

    private d a(int i, String str) {
        String[] strArrSplit;
        int length;
        if (str != null && (length = (strArrSplit = str.split(" ")).length) >= 2) {
            try {
                return new d(traviaut.m.a(strArrSplit, length - 2), i, this.b[i], Integer.parseInt(strArrSplit[length - 1]));
            } catch (NumberFormatException unused) {
                return new d();
            }
        }
        return new d();
    }

    public final int a(String str, traviaut.n nVar) {
        if (str.length() == 0) {
            return 0;
        }
        d dVarA = a(nVar, 0, str);
        d[] dVarArr = this.d;
        for (int i = 0; i < 41; i++) {
            d dVar = dVarArr[i];
            if (dVar.a(dVarA)) {
                c(dVar.a);
                return dVar.a;
            }
        }
        return 0;
    }

    public final void c(int i) {
        if (i < 19) {
            if (this.h > 0) {
                return;
            } else {
                this.h = i;
            }
        } else if (this.i > 0) {
            return;
        } else {
            this.i = i;
        }
        this.e[i] = this.d[i].d();
    }

    public final d d(int i) {
        return this.e[i];
    }

    public final int e(int i) {
        return this.c[i];
    }

    public final boolean d() {
        return b() && this.j;
    }

    public final void a(h hVar, traviaut.e eVar) {
        int i;
        this.i = 0;
        this.h = 0;
        String str = eVar.e.a().compareTo(traviaut.n.VER35n) < 0 ? "id" : "class";
        int i2 = 1;
        while (true) {
            if (i2 >= a.a()) {
                i = -1;
                break;
            } else {
                if (hVar.a("div", str, "f" + i2) != null) {
                    i = i2;
                    break;
                }
                i2++;
            }
        }
        this.a = i;
        if (this.a > 0) {
            int[] iArrA = a.a(this.a);
            System.arraycopy(iArrA, 0, this.b, 1, iArrA.length);
        }
        c(hVar, eVar);
        a(hVar);
    }

    public final void h() {
        int i;
        for (int i2 = 1; i2 < 41; i2++) {
            int i3 = this.d[i2].b;
            if (i3 != 0 && this.c[i3] < (i = this.d[i2].c)) {
                this.c[i3] = i;
            }
        }
        for (int i4 = 19; i4 < 41; i4++) {
            if (this.d[i4].b == 34) {
                this.f = true;
            }
        }
        if (this.f) {
            this.g = false;
            return;
        }
        for (int i5 = 1; i5 < 19; i5++) {
            int i6 = this.e[i5].c;
            if (i6 > 10) {
                this.f = true;
            }
            if (i6 != 10) {
                this.g = false;
            }
        }
    }

    public final void b(h hVar, traviaut.e eVar) {
        this.i = 0;
        if (eVar.e.a().compareTo(traviaut.n.VER35) < 0) {
            int[] iArr = this.b;
            for (int i = 1; i < iArr.length; i++) {
                iArr[i + 19] = 0;
                Element elementA = hVar.a("img", "class", "d" + i);
                if (elementA != null) {
                    String[] strArrSplit = elementA.getAttribute("src").split("/");
                    String str = strArrSplit[strArrSplit.length - 1];
                    if (!"iso.gif".equals(str)) {
                        int iIndexOf = str.indexOf(46);
                        int i2 = iIndexOf;
                        if (iIndexOf >= 2) {
                            int iIndexOf2 = str.indexOf(98);
                            if (iIndexOf2 > 0) {
                                i2 = iIndexOf2;
                            }
                            iArr[i + 19] = Integer.parseInt(str.substring(1, i2));
                        }
                    }
                }
            }
        } else {
            a(hVar, this.b);
        }
        if (hVar.a("img", "src", "img/un/g/g40.gif") != null) {
            this.b[26] = 40;
        }
        c(hVar, eVar);
        if (this.d[40].c > 0 && this.b[40] == 0) {
            int i3 = eVar.e.c().b;
            this.b[40] = i3;
            d dVar = this.d[40];
            d[] dVarArr = this.e;
            d[] dVarArr2 = this.d;
            d dVar2 = new d(dVar.a(), 40, i3, dVar.c);
            dVarArr2[40] = dVar2;
            dVarArr[40] = dVar2;
        }
        this.j = true;
    }

    private void a(h hVar, traviaut.e eVar, String str) {
        Element elementA = hVar.a("map", "name", str);
        if (elementA == null) {
            return;
        }
        NodeList elementsByTagName = elementA.getElementsByTagName("area");
        for (int i = 0; i < elementsByTagName.getLength(); i++) {
            Element element = (Element) elementsByTagName.item(i);
            String attribute = element.getAttribute("href");
            int iLastIndexOf = attribute.lastIndexOf(61);
            if (iLastIndexOf >= 0) {
                int i2 = Integer.parseInt(attribute.substring(iLastIndexOf + 1));
                String attribute2 = element.getAttribute("alt");
                String attribute3 = attribute2;
                if (attribute2.isEmpty()) {
                    attribute3 = element.getAttribute("title");
                }
                d[] dVarArr = this.e;
                d[] dVarArr2 = this.d;
                d dVarA = a(eVar.e.a(), i2, attribute3);
                dVarArr2[i2] = dVarA;
                dVarArr[i2] = dVarA;
            }
        }
    }

    private d a(traviaut.n nVar, int i, String str) {
        return nVar.compareTo(traviaut.n.VER40a) < 0 ? a(i, str) : str == null ? new d() : a(i, str.split("\\|\\|")[0].replaceAll("\\<.*?>", ""));
    }

    private static void a(h hVar, int[] iArr) {
        Element elementA = hVar.a("div", "id", "content");
        if (elementA == null) {
            return;
        }
        NodeList elementsByTagName = elementA.getElementsByTagName("img");
        if (elementsByTagName.getLength() < 22) {
            return;
        }
        for (int i = 0; i < 22; i++) {
            iArr[i + 19] = 0;
            String[] strArrSplit = ((Element) elementsByTagName.item(i)).getAttribute("class").split(" ");
            if (strArrSplit.length >= 2) {
                String str = strArrSplit[strArrSplit.length - 1];
                if (!"iso".equals(str)) {
                    iArr[i + 19] = traviaut.m.c(str);
                }
            }
        }
    }

    private void a(h hVar) {
        Element elementA = hVar.a("div", "id", "village_map");
        if (elementA == null) {
            return;
        }
        NodeList elementsByTagName = elementA.getElementsByTagName("div");
        if (elementsByTagName.getLength() != 19) {
            return;
        }
        for (int i = 1; i < 19; i++) {
            if (((Element) elementsByTagName.item(i)).getAttribute("class").contains("underConstruction")) {
                c(i);
                return;
            }
        }
    }
}
