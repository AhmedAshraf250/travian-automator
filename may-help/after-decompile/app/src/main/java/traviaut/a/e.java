package traviaut.a;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/e.class */
public final class e {
    private static final AnonymousClass1[][] a = {new AnonymousClass1[0], new AnonymousClass1[0], new AnonymousClass1[0], new AnonymousClass1[0], new AnonymousClass1[0], new AnonymousClass1[]{new AnonymousClass1(15, 5, 0), new AnonymousClass1(1, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 5, 0), new AnonymousClass1(2, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 5, 0), new AnonymousClass1(3, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(4, 5, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 5, 0), new AnonymousClass1(8, 5, 0), new AnonymousClass1(4, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 1, 0), new AnonymousClass1(10, 20, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 1, 0), new AnonymousClass1(11, 20, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 3, 0), new AnonymousClass1(22, 3, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 3, 0), new AnonymousClass1(22, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(16, 15, 0)}, new AnonymousClass1[0], new AnonymousClass1[0], new AnonymousClass1[]{new AnonymousClass1(15, 3, 0), new AnonymousClass1(10, 1, 0), new AnonymousClass1(11, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 3, 0), new AnonymousClass1(16, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(13, 3, 0), new AnonymousClass1(22, 5, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 5, 0), new AnonymousClass1(22, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 3, 0), new AnonymousClass1(19, 3, 0)}, new AnonymousClass1[]{new AnonymousClass1(23, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 10, 0), new AnonymousClass1(22, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 5, 0), new AnonymousClass1(26, 0, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 5, 0), new AnonymousClass1(18, 1, 0), new AnonymousClass1(25, 0, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(20, 10, 0), new AnonymousClass1(17, 20, 0)}, new AnonymousClass1[]{new AnonymousClass1(19, 20, 0)}, new AnonymousClass1[]{new AnonymousClass1(20, 20, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 5, 0)}, new AnonymousClass1[]{new AnonymousClass1(11, 20, 0), new AnonymousClass1(16, 10, 0)}, new AnonymousClass1[]{new AnonymousClass1(16, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 3, 0), new AnonymousClass1(16, 1, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 10, 0), new AnonymousClass1(38, 20, 0)}, new AnonymousClass1[]{new AnonymousClass1(15, 10, 0), new AnonymousClass1(39, 20, 0)}, new AnonymousClass1[0], new AnonymousClass1[]{new AnonymousClass1(16, 10, 0), new AnonymousClass1(20, 20, 0)}};

    public static boolean a(int i, traviaut.b.b bVar) {
        AnonymousClass1 anonymousClass1 = null;
        for (AnonymousClass1 anonymousClass12 : a[i]) {
            if (i == anonymousClass12.a) {
                anonymousClass1 = anonymousClass12;
            } else {
                int iE = bVar.e(anonymousClass12.a);
                if (iE < anonymousClass12.b) {
                    return false;
                }
                if (anonymousClass12.b == 0 && iE > 0) {
                    return false;
                }
            }
        }
        int iE2 = bVar.e(i);
        if (iE2 > 0) {
            return anonymousClass1 != null && iE2 >= anonymousClass1.b;
        }
        return true;
    }

    /* JADX INFO: renamed from: traviaut.a.e$1, reason: invalid class name */
    /* JADX INFO: loaded from: traviaut.jar:traviaut/a/e$1.class */
    static /* synthetic */ class AnonymousClass1 {
        public final int a;
        public final int b;

        private AnonymousClass1(int i, int i2) {
            this.a = i;
            this.b = i2;
        }

        /* synthetic */ AnonymousClass1(int i, int i2, byte b) {
            this(i, i2);
        }
    }
}
