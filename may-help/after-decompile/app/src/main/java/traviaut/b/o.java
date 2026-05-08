package traviaut.b;

import java.util.ArrayList;
import java.util.Collection;
import java.util.HashMap;
import java.util.List;
import java.util.stream.Stream;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/o.class */
public final class o {
    public final List<r> a = new ArrayList();
    private int b = -1;

    public final int a() {
        return this.b;
    }

    public final Collection<r> a(r rVar) {
        u uVar = new u(rVar);
        Stream<r> streamFilter = this.a.stream().filter(rVar2 -> {
            return rVar2.a != rVar.a;
        });
        uVar.getClass();
        streamFilter.forEach(uVar::a);
        uVar.a();
        return uVar.a;
    }

    public final void b() {
        this.a.stream().forEach(rVar -> {
            rVar.f.e();
        });
    }

    public final boolean a(List<r> list, int i) {
        boolean z;
        if (i != -1) {
            this.b = i;
        }
        if (this.a.size() == list.size()) {
            int i2 = 0;
            while (true) {
                if (i2 >= list.size()) {
                    z = true;
                    break;
                }
                if (this.a.get(i2).a != list.get(i2).a) {
                    z = false;
                    break;
                }
                i2++;
            }
        } else {
            z = false;
        }
        if (z) {
            return false;
        }
        HashMap map = new HashMap();
        for (r rVar : this.a) {
            map.put(Integer.valueOf(rVar.a), rVar);
        }
        this.a.clear();
        for (r rVar2 : list) {
            r rVar3 = (r) map.get(Integer.valueOf(rVar2.a));
            r rVar4 = rVar3;
            if (rVar3 == null) {
                rVar4 = rVar2;
            }
            this.a.add(rVar4);
        }
        return true;
    }
}
