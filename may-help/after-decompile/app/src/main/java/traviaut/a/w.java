package traviaut.a;

import traviaut.Main;
import traviaut.a.x;
import traviaut.xml.TATroopsQueue;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/w.class */
public final class w {
    private final traviaut.b.r a;
    private final x.a[] b;

    public w(traviaut.b.r rVar) {
        this.a = rVar;
        this.b = rVar.d.d();
    }

    public final TATroopsQueue a(int i) {
        return traviaut.f.c().troopstraining.queue[this.b[i].a()];
    }

    public final boolean b(int i) {
        return a(i, System.currentTimeMillis(), 0L);
    }

    public final boolean a(int i, long j, long j2) {
        return this.a.f.a(j, this.b[i].b, a(i).getQueueMillis() + j2);
    }

    public final traviaut.b.l c(int i) {
        return this.b[i].a.a(a(i).getMin());
    }

    public final int d(int i) {
        if (Main.b()) {
            return a(i).amount;
        }
        switch (this.b[i].a()) {
            case 0:
                return 90;
            case 3:
                return 10;
            default:
                return 30;
        }
    }

    public final traviaut.b.l e(int i) {
        return this.b[i].a.a(d(i));
    }
}
