package traviaut.b;

import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Set;
import traviaut.xml.TABuildLayout;
import traviaut.xml.TABuildPlace;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/c.class */
public final class c implements Comparator<d> {
    private final r a;
    private final boolean b;
    private final boolean c;
    private l d;

    public c(r rVar, boolean z) {
        this.a = rVar;
        this.c = z;
        this.b = z;
    }

    private void b(s sVar) {
        if (sVar.l.c()) {
            TABuildLayout tABuildLayoutB = this.a.b();
            b bVar = sVar.l;
            for (int i = 19; i < 41; i++) {
                d dVarD = bVar.d(i);
                if (dVarD.b()) {
                    dVarD.d = 10;
                    TABuildPlace place = tABuildLayoutB.getPlace(i);
                    if (dVarD.b == place.gid || (dVarD.a == 40 && place.gid == 100)) {
                        dVarD.d = place.priority;
                    } else {
                        int i2 = 19;
                        while (true) {
                            if (i2 >= 41) {
                                break;
                            }
                            if (dVarD.b == tABuildLayoutB.getPlace(i2).gid) {
                                dVarD.d = tABuildLayoutB.getPlace(i2).priority;
                                break;
                            }
                            i2++;
                        }
                    }
                }
            }
        }
    }

    public final Set<Integer> a() {
        s sVarA = this.a.a();
        this.d = sVarA.c();
        b bVar = sVarA.l;
        ArrayList arrayList = new ArrayList(41);
        a(sVarA);
        b(sVarA);
        if (this.a.e.builder.upresources && (!this.c || !bVar.e())) {
            a(1, 19, bVar, arrayList);
        }
        if (this.a.e.builder.upbuildings && (!this.c || !bVar.f())) {
            a(19, 41, bVar, arrayList);
        }
        Collections.sort(arrayList, this);
        boolean[] zArr = new boolean[5];
        LinkedHashSet linkedHashSet = new LinkedHashSet();
        for (d dVar : arrayList) {
            if (dVar.c()) {
                if (!zArr[dVar.b]) {
                    zArr[dVar.b] = true;
                }
            }
            linkedHashSet.add(Integer.valueOf(dVar.a));
        }
        return linkedHashSet;
    }

    private void a(s sVar) {
        if (sVar.l.b()) {
            l lVarA = this.a.d.e.d().a.a();
            lVarA.d();
            int[] iArrE = lVarA.e();
            int[] iArr = new int[4];
            int[] iArr2 = this.a.b().resPriorities;
            for (int i = 0; i < 4; i++) {
                int i2 = 0;
                for (int i3 = 1; i3 < iArrE.length; i3++) {
                    if (iArrE[i2] > iArrE[i3]) {
                        i2 = i3;
                    }
                }
                iArrE[i2] = Integer.MAX_VALUE;
                iArr[i2] = iArr2[i];
            }
            int iB = sVar.e.b();
            if (this.a.d.e.a().compareTo(traviaut.n.VER42) >= 0) {
                iB = Math.min(iB, sVar.f);
            }
            if (iB < 30 || this.a.d.e.d().b.a()) {
                iArr[3] = iArr2[4];
            }
            for (int i4 = 1; i4 < 19; i4++) {
                d dVarD = sVar.l.d(i4);
                dVarD.d = iArr[dVarD.b - 1];
            }
        }
    }

    private void a(int i, int i2, b bVar, List<d> list) {
        boolean zIsMaxLvl;
        for (int i3 = i; i3 < i2; i3++) {
            d dVarD = bVar.d(i3);
            if (dVarD.b() && this.a.e.builder.buildings.isSet(i3) && dVarD.d != 0 && !dVarD.b(this.a.d, bVar.a())) {
                if (dVarD.a >= 19) {
                    TABuildLayout tABuildLayoutB = this.a.b();
                    TABuildPlace place = tABuildLayoutB.getPlace(dVarD.a);
                    if (place.gid == dVarD.b || dVarD.a == 40) {
                        zIsMaxLvl = place.isMaxLvl(dVarD.c);
                    } else {
                        int i4 = 19;
                        while (true) {
                            if (i4 >= 41) {
                                zIsMaxLvl = false;
                                break;
                            }
                            TABuildPlace place2 = tABuildLayoutB.getPlace(i4);
                            if (place2.gid == dVarD.b) {
                                zIsMaxLvl = place2.isMaxLvl(dVarD.c);
                                break;
                            }
                            i4++;
                        }
                    }
                } else {
                    zIsMaxLvl = traviaut.f.c().resmaxlvl <= dVarD.c;
                }
                if (!zIsMaxLvl && dVarD.a(this.a.d, this.a.a().d) && (!this.b || dVarD.a(this.a.d, this.d))) {
                    list.add(dVarD);
                }
            }
        }
    }

    @Override // java.util.Comparator
    public final /* bridge */ /* synthetic */ int compare(d dVar, d dVar2) {
        d dVar3 = dVar;
        d dVar4 = dVar2;
        int i = dVar3.c;
        int i2 = dVar3.d;
        return (i * dVar4.d) - (dVar4.c * i2);
    }
}
