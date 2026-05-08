package org.a;

/* JADX INFO: loaded from: traviaut.jar:org/a/b.class */
public final class b extends Exception {
    public b(String str) {
        super(str);
    }

    public b(Throwable th) {
        super(th.getMessage());
    }
}
