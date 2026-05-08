package traviaut.e;

import java.io.Reader;
import java.util.Scanner;
import java.util.regex.Pattern;
import traviaut.b.l;

/* JADX INFO: loaded from: traviaut.jar:traviaut/e/a.class */
public final class a implements AutoCloseable {
    private final Scanner a;
    private final Pattern b = Pattern.compile("\\d+");
    private final Pattern c = Pattern.compile("\\d+\\.?\\d*");
    private final Pattern d = Pattern.compile(",?");
    private final Pattern e = Pattern.compile(".*");

    public a(Reader reader) {
        this.a = new Scanner(reader).useDelimiter("");
    }

    @Override // java.lang.AutoCloseable
    public final void close() {
        this.a.close();
    }

    public final boolean a() {
        return this.a.hasNext();
    }

    public final int b() {
        return Integer.parseInt(this.a.findWithinHorizon(this.b, 0));
    }

    public final double c() {
        return Double.parseDouble(this.a.findWithinHorizon(this.c, 0));
    }

    public final void d() {
        this.a.skip(this.d);
    }

    public final String e() {
        return this.a.findInLine(this.e).trim();
    }

    public final l f() {
        this.a.skip(" *\\[");
        int[] iArr = new int[4];
        for (int i = 0; i < 4; i++) {
            iArr[i] = b();
            d();
        }
        this.a.skip("\\]");
        return new l(iArr);
    }
}
