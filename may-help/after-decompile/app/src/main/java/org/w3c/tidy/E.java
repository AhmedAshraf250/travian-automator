package org.w3c.tidy;

import java.io.IOException;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.io.UnsupportedEncodingException;
import java.io.Writer;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/E.class */
public final class E implements AutoCloseable, D {
    private Writer a;
    private char[] b;

    public E(C0009h c0009h, String str, OutputStream outputStream) throws UnsupportedEncodingException {
        this.a = new OutputStreamWriter(outputStream, str);
        this.b = c0009h.as;
    }

    @Override // org.w3c.tidy.D
    public final void a(int i) {
        try {
            this.a.write(i);
        } catch (IOException e) {
            System.err.println("OutJavaImpl.outc: " + e.getMessage());
        }
    }

    @Override // org.w3c.tidy.D
    public final void a() {
        try {
            this.a.write(this.b);
        } catch (IOException e) {
            System.err.println("OutJavaImpl.newline: " + e.getMessage());
        }
    }

    @Override // org.w3c.tidy.D
    public final void b() {
        try {
            this.a.flush();
        } catch (IOException e) {
            System.err.println("OutJavaImpl.flush: " + e.getMessage());
        }
    }

    @Override // java.lang.AutoCloseable
    public final void close() throws Exception {
        this.a.flush();
        this.a.close();
    }
}
