package traviaut.b;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/m.class */
public final class m {
    private int c;
    private int d;
    public final b b;
    private boolean f;
    public final l a = new l();
    private int e = 20;
    private final l g = new l();

    /* JADX WARN: $VALUES field not found */
    /* JADX WARN: Failed to restore enum class, 'enum' modifier and super class removed */
    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/m$a.class */
    public static final class a {
        public static final int a = 1;
        public static final int b = 2;
        public static final int c = 3;
        private static final /* synthetic */ int[] d = {1, 2, 3};
    }

    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/m$b.class */
    public enum b {
        EQ_TRADE { // from class: traviaut.b.m.b.1
            @Override // traviaut.b.m.b
            final l a(l lVar, int i) {
                return lVar.f(i);
            }
        },
        EQ_REMAINING { // from class: traviaut.b.m.b.2
            @Override // traviaut.b.m.b
            final l a(l lVar, int i) {
                return lVar.g(i);
            }
        };

        abstract l a(l lVar, int i);

        /* synthetic */ b(byte b) {
            this();
        }
    }

    public m(b bVar) {
        this.b = bVar;
    }

    public final m a(l lVar) {
        this.d = a.a;
        this.a.a(lVar);
        return this;
    }

    public final m a(int i) {
        this.d = a.b;
        this.c = i;
        return this;
    }

    public final m b(int i) {
        this.d = a.c;
        this.e = i;
        return this;
    }

    public final m a() {
        this.f = true;
        return this;
    }

    public final boolean b() {
        return this.d != a.c && this.a.i() == 0 && this.c == 0;
    }

    public final String c() {
        return "sent: " + this.g + " total: " + this.g.i();
    }

    public final l a(l lVar, int i, int i2) {
        if (this.f && lVar.i() < i2) {
            return null;
        }
        if (i <= 0 || this.e < i) {
            i = this.e;
        }
        l lVarF = lVar;
        if (this.d == a.a) {
            lVarF = lVar.c(this.a).f();
        }
        int iMin = Math.min(i * i2, lVarF.i());
        if (this.d == a.b && this.c < iMin) {
            iMin = this.c;
        }
        if (this.f) {
            iMin = Math.min(i, iMin / i2) * i2;
        }
        return lVarF.i() <= iMin ? lVarF : this.b.a(lVarF, iMin);
    }

    public final void b(l lVar) {
        this.g.a(lVar);
        if (this.d == a.a) {
            this.a.a(lVar.a(-1));
        } else if (this.d == a.b) {
            this.c -= lVar.i();
        }
    }
}
