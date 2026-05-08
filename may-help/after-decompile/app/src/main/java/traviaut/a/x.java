package traviaut.a;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/x.class */
public final class x {
    private static final a[][] a = new a[3];

    public static a[] a(traviaut.l lVar) {
        return a[lVar.a()];
    }

    /* JADX INFO: loaded from: traviaut.jar:traviaut/a/x$a.class */
    public static class a {
        public final traviaut.b.l a;
        private int d;
        public final int b;
        public final String c;

        public a(String str) {
            String[] strArrSplit = str.split("\\s+", 11);
            int[] iArr = new int[4];
            for (int i = 0; i < 4; i++) {
                iArr[i] = Integer.parseInt(strArrSplit[i]);
            }
            this.a = new traviaut.b.l(iArr);
            this.d = Integer.parseInt(strArrSplit[7]);
            this.b = Integer.parseInt(strArrSplit[8]);
            this.c = strArrSplit[10];
        }

        public final int a() {
            if (this.d == 4) {
                return 2;
            }
            switch (this.b) {
                case 0:
                    return 0;
                case 1:
                    return 1;
                case 2:
                    return 3;
                default:
                    return -1;
            }
        }
    }

    /* JADX WARN: Type inference failed for: r0v1, types: [traviaut.a.x$a[], traviaut.a.x$a[][]] */
    static {
        try {
            InputStream resourceAsStream = Thread.currentThread().getContextClassLoader().getResourceAsStream("traviaut/res/troops.txt");
            try {
                BufferedReader bufferedReader = new BufferedReader(new InputStreamReader(resourceAsStream));
                Throwable th = null;
                try {
                    try {
                        bufferedReader.readLine();
                        for (int i = 0; i < 3; i++) {
                            a[i] = new a[10];
                            th = null;
                            for (int i2 = 0; i2 < 10; i2++) {
                                a[i][i2] = new a(bufferedReader.readLine());
                            }
                            bufferedReader.readLine();
                        }
                        bufferedReader.close();
                        if (resourceAsStream != null) {
                            resourceAsStream.close();
                        }
                    } catch (Throwable th2) {
                        throw th2;
                    }
                } catch (Throwable th3) {
                    if (0 != 0) {
                        try {
                            bufferedReader.close();
                        } catch (Throwable th4) {
                            th.addSuppressed(th4);
                        }
                    } else {
                        bufferedReader.close();
                    }
                    throw th3;
                }
            } finally {
            }
        } catch (IOException e) {
            traviaut.g.a("failed to init troop DB", e);
        }
    }
}
