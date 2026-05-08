package traviaut.a;

import java.util.concurrent.TimeUnit;
import traviaut.k;
import traviaut.xml.TAGlobalSets;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/o.class */
public final class o implements k {
    private boolean a = true;
    private long b;

    public final boolean a(traviaut.e eVar) {
        if (traviaut.f.c().logouten) {
            return this.b < System.currentTimeMillis();
        }
        if (!this.a) {
            return false;
        }
        b(eVar);
        return false;
    }

    private void b(traviaut.e eVar) {
        this.b = 0L;
        this.a = false;
        eVar.d.c();
    }

    @Override // traviaut.a.k
    public final void a(k.a aVar) throws traviaut.b.i {
        long jCurrentTimeMillis = System.currentTimeMillis();
        if (this.b > jCurrentTimeMillis) {
            return;
        }
        TAGlobalSets tAGlobalSetsC = traviaut.f.c();
        if (this.a) {
            b(aVar.c);
            this.b = jCurrentTimeMillis + a(tAGlobalSetsC.loginTime, tAGlobalSetsC.logoutVar);
            return;
        }
        this.a = true;
        this.b = a(tAGlobalSetsC.logoutTime, tAGlobalSetsC.logoutVar);
        aVar.c.a("logout for " + ((int) TimeUnit.MILLISECONDS.toMinutes(this.b)) + " minutes");
        aVar.c.b("logout.php");
        aVar.c.d.a(this.b, "logout period");
        this.b += System.currentTimeMillis();
        traviaut.k.a().b(aVar.c);
    }

    private static long a(int i, int i2) {
        long millis = TimeUnit.MINUTES.toMillis(i);
        if (i2 != 0) {
            long j = (millis * ((long) i2)) / 100;
            millis += ((long) traviaut.m.a.nextInt(2 * ((int) j))) - j;
        }
        return millis;
    }
}
