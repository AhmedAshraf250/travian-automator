package traviaut.b;

import java.io.InputStreamReader;
import java.util.Scanner;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/a.class */
public final class a {
    private static final int[][] a = new int[13];
    private static final C0001a[] b = new C0001a[42];
    private static final C0001a[] c = new C0001a[42];
    private static final C0001a[] d = new C0001a[42];

    /* JADX INFO: renamed from: traviaut.b.a$a, reason: collision with other inner class name */
    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/a$a.class */
    public static class C0001a {
        private l c;
        private double d;
        public final int a;
        public final String b;

        protected C0001a(l lVar, double d, int i, String str) {
            this.c = lVar;
            this.d = d;
            this.a = i;
            this.b = str;
        }

        public final l a(int i) {
            int[] iArrE = this.c.e();
            double dPow = Math.pow(this.d, i - 1);
            for (int i2 = 0; i2 < 4; i2++) {
                iArrE[i2] = Math.round((float) ((((double) iArrE[i2]) * dPow) / 5.0d)) * 5;
            }
            return new l(iArrE).b(1000000);
        }
    }

    public static int a() {
        return 13;
    }

    public static int[] a(int i) {
        return a[i];
    }

    public static C0001a[] a(traviaut.n nVar) {
        return traviaut.a.a(nVar) ? b : c;
    }

    public static C0001a[] b() {
        return b;
    }

    private static C0001a a(traviaut.e.a aVar) {
        l lVarF = aVar.f();
        double dC = aVar.c();
        aVar.d();
        int iB = aVar.b();
        aVar.d();
        return new C0001a(lVarF, dC, iB, aVar.e());
    }

    private static void c() {
        Scanner scanner = new Scanner(Thread.currentThread().getContextClassLoader().getResourceAsStream("traviaut/res/reslayouts.txt"));
        Throwable th = null;
        try {
            int i = 1;
            while (true) {
                int i2 = i;
                if (i2 > 12) {
                    scanner.close();
                    return;
                }
                try {
                    a[i] = new int[18];
                    int i3 = 0;
                    while (true) {
                        i2 = i3;
                        if (i2 < a[i].length) {
                            a[i][i3] = scanner.nextInt();
                            i3++;
                        }
                    }
                    i++;
                } catch (Throwable th2) {
                    throw th2;
                }
            }
        } catch (Throwable th3) {
            if (0 != 0) {
                try {
                    scanner.close();
                } catch (Throwable th4) {
                    th.addSuppressed(th4);
                }
            } else {
                scanner.close();
            }
            throw th3;
        }
    }

    /* JADX WARN: Type inference failed for: r0v1, types: [int[], int[][]] */
    static {
        traviaut.e.a aVar = new traviaut.e.a(new InputStreamReader(Thread.currentThread().getContextClassLoader().getResourceAsStream("traviaut/res/buildings.txt")));
        int iB = 0;
        while (iB < 41) {
            iB = aVar.b();
            C0001a[] c0001aArr = b;
            C0001a[] c0001aArr2 = d;
            C0001a[] c0001aArr3 = c;
            C0001a c0001aA = a(aVar);
            c0001aArr3[iB] = c0001aA;
            c0001aArr2[iB] = c0001aA;
            c0001aArr[iB] = c0001aA;
        }
        for (int i = 0; i < 2; i++) {
            b[aVar.b()] = a(aVar);
        }
        for (int i2 = 0; i2 < 2; i2++) {
            d[aVar.b()] = a(aVar);
        }
        aVar.close();
        c();
    }
}
