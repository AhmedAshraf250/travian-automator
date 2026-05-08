package traviaut.a;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/b.class */
public abstract class b implements k {
    public abstract boolean a(traviaut.b.r rVar);

    protected abstract void a(long j, traviaut.b.r rVar, traviaut.b.l lVar);

    protected abstract boolean b(traviaut.b.r rVar);

    public void b(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        if (a(rVar)) {
            a(j, rVar, lVar);
        }
    }

    public boolean c(traviaut.b.r rVar) {
        if (a(rVar)) {
            return b(rVar);
        }
        return false;
    }
}
