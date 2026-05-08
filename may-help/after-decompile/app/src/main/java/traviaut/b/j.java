package traviaut.b;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/j.class */
public final class j {
    public final h a;
    public final boolean b;
    public final boolean c;
    private boolean d;

    public j(h hVar) {
        this(hVar, false, false);
    }

    public j(h hVar, boolean z) {
        this(hVar, true, z);
    }

    private j(h hVar, boolean z, boolean z2) {
        this.d = true;
        this.a = hVar;
        this.b = z;
        this.c = z2;
    }

    public final j a() {
        this.d = false;
        return this;
    }

    public final boolean b() {
        return this.b && this.c;
    }

    public final boolean c() {
        return this.d;
    }
}
