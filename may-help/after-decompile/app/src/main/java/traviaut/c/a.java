package traviaut.c;

import java.io.ByteArrayOutputStream;
import org.w3c.dom.Element;
import org.w3c.tidy.B;
import org.w3c.tidy.C0009h;
import org.w3c.tidy.C0018q;
import org.w3c.tidy.D;
import org.w3c.tidy.E;
import org.w3c.tidy.F;
import org.w3c.tidy.K;
import org.w3c.tidy.L;
import org.w3c.tidy.Q;

/* JADX INFO: loaded from: traviaut.jar:traviaut/c/a.class */
public final class a {
    private static final C0009h a;
    private static final B b;
    private static final F c;

    /* JADX WARN: Multi-variable type inference failed */
    public static String a(Element element) {
        ByteArrayOutputStream byteArrayOutputStream = new ByteArrayOutputStream();
        C0018q c0018q = (C0018q) element;
        try {
            E e = new E(a, "UTF-8", byteArrayOutputStream);
            try {
                c.a((D) e, (short) 0, 0, b, c0018q.b);
                e.close();
            } finally {
            }
        } catch (Exception unused) {
        }
        return byteArrayOutputStream.toString();
    }

    static {
        K k = new K();
        C0009h c0009h = new C0009h(k);
        a = c0009h;
        c0009h.as = " ".toCharArray();
        a.b = 1000000;
        Q q = new Q();
        q.a(a);
        a.ap = q;
        b = new B(new L(null, 0), a, k);
        c = new F(a);
    }
}
