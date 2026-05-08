package traviaut.b;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/i.class */
public final class i extends Exception {
    private final int a;

    public i(String str) {
        this(str, 0);
    }

    public i(String str, int i) {
        super(str);
        this.a = i;
    }

    public i(String str, Throwable th) {
        super(str, th);
        this.a = 0;
    }

    public final boolean a() {
        return this.a > 0;
    }

    public final void a(traviaut.d dVar) {
        dVar.a(this.a, getMessage());
    }
}
