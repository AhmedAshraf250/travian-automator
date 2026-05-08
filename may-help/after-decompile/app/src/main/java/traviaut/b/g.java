package traviaut.b;

import java.io.InputStreamReader;
import java.util.HashMap;
import java.util.Map;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/g.class */
public final class g {
    public static final Map<Integer, String> a = new HashMap();

    public static boolean a(int i) {
        return (i < 106 || i == 108 || i == 110 || i == 111) ? false : true;
    }

    /* JADX WARN: Multi-variable type inference failed */
    /* JADX WARN: Type inference failed for: r0v14, types: [boolean] */
    /* JADX WARN: Type inference failed for: r0v22 */
    /* JADX WARN: Type inference failed for: r0v24 */
    /* JADX WARN: Type inference failed for: r0v5 */
    /* JADX WARN: Type inference failed for: r0v6 */
    /* JADX WARN: Type inference failed for: r0v8 */
    /* JADX WARN: Type inference failed for: r0v9, types: [java.lang.Throwable] */
    /* JADX WARN: Type inference failed for: r8v0 */
    /* JADX WARN: Type inference failed for: r8v1 */
    /* JADX WARN: Type inference failed for: r8v2 */
    static {
        traviaut.e.a aVar = new traviaut.e.a(new InputStreamReader(Thread.currentThread().getContextClassLoader().getResourceAsStream("traviaut/res/hero_items.txt")));
        ?? A = 0;
        boolean z = false ? 1 : 0;
        while (true) {
            try {
                try {
                    A = aVar.a();
                    if (A == 0) {
                        aVar.close();
                        return;
                    } else {
                        A = a.put(Integer.valueOf(aVar.b()), aVar.e());
                    }
                } finally {
                     = A;
                }
            } catch (Throwable th) {
                if ( != 0) {
                    try {
                        aVar.close();
                    } catch (Throwable th2) {
                        addSuppressed(th2);
                    }
                } else {
                    aVar.close();
                }
                throw th;
            }
        }
    }
}
