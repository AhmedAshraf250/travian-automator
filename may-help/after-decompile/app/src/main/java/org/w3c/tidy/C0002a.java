package org.w3c.tidy;

import java.io.InputStream;
import java.io.OutputStream;
import java.io.UnsupportedEncodingException;

/* JADX INFO: renamed from: org.w3c.tidy.a, reason: case insensitive filesystem */
/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/a.class */
public class C0002a {
    protected String a;
    protected C0002a b;
    protected C c;

    public static D a(C0009h c0009h, OutputStream outputStream) {
        try {
            return new E(c0009h, c0009h.c(), outputStream);
        } catch (UnsupportedEncodingException e) {
            throw new RuntimeException("Unsupported encoding: " + e.getMessage());
        }
    }

    public static L a(C0009h c0009h, InputStream inputStream) {
        try {
            return new L(inputStream, c0009h.b(), c0009h.c);
        } catch (UnsupportedEncodingException e) {
            throw new RuntimeException("Unsupported encoding: " + e.getMessage());
        }
    }
}
